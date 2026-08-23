<?php
/**
 * Panel de asignación de turnos: quién está de turno ahora, próximos turnos, y un
 * formulario para asignar uno nuevo. Toda escritura exige nonce + capacidad
 * (`edit_others_posts`, nivel editor) — mismo criterio de seguridad que el resto de
 * la plataforma (ver docs/Architecture.md, "Seguridad").
 *
 * @package DNorteCore\Workflow\Shifts
 */

declare(strict_types=1);

namespace DNorteCore\Workflow\Shifts;

use DNorteCore\Admin\AdminPage;
use DNorteCore\Admin\Contracts\RegistersAdminPages;
use DNorteCore\Config\Config;

final class ShiftsAdminPage implements RegistersAdminPages {

	private const CAPABILITY   = 'edit_others_posts';
	private const NONCE_ACTION = 'dnorte_shifts_manage';
	private const NONCE_FIELD  = 'dnorte_shifts_nonce';

	public function __construct(
		private readonly ShiftRepository $shifts,
		private readonly Config $config
	) {
	}

	/**
	 * @return list<AdminPage>
	 */
	public function adminPages(): array {
		return array(
			new AdminPage(
				'dnorte-turnos',
				__( 'Turnos', 'dnorte-core' ),
				__( 'Turnos', 'dnorte-core' ),
				self::CAPABILITY,
				$this->render( ... ),
				10,
				'dashicons-groups'
			),
		);
	}

	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'dnorte-core' ) );
		}

		$notice = $this->handleRequest();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Turnos', 'dnorte-core' ) . '</h1>';

		if ( $notice !== null ) {
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $notice['type'] ),
				esc_html( $notice['message'] )
			);
		}

		$this->renderOnDutyNow();
		$this->renderForm();
		$this->renderUpcomingTable();

		echo '</div>';
	}

	/**
	 * @return array{type: string, message: string}|null
	 */
	private function handleRequest(): ?array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- la verificación real ocurre dos líneas más abajo (check_admin_referer); esta sola comprueba que el formulario correcto fue el que se envió, antes de tocar nada.
		if ( ! isset( $_POST['dnorte_shifts_action'] ) && ! isset( $_GET['dnorte_shifts_action'] ) ) {
			return null;
		}

		if ( isset( $_POST['dnorte_shifts_action'] ) && sanitize_key( wp_unslash( $_POST['dnorte_shifts_action'] ) ) === 'create' ) {
			check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

			return $this->handleCreate();
		}

		if ( isset( $_GET['dnorte_shifts_action'] ) && sanitize_key( wp_unslash( $_GET['dnorte_shifts_action'] ) ) === 'delete' ) {
			check_admin_referer( self::NONCE_ACTION );

			return $this->handleDelete();
		}

		return null;
	}

	/**
	 * Los cinco accesos a $_POST de abajo ya pasaron por check_admin_referer() en
	 * handleRequest() (el único método que llama a este) antes de llegar aquí — la
	 * sniff no puede ver a través de esa llamada entre métodos.
	 *
	 * @return array{type: string, message: string}
	 */
	private function handleCreate(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$userId = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$role = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$starts = isset( $_POST['starts_at'] ) ? sanitize_text_field( wp_unslash( $_POST['starts_at'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$ends = isset( $_POST['ends_at'] ) ? sanitize_text_field( wp_unslash( $_POST['ends_at'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$notes = isset( $_POST['notes'] ) ? sanitize_text_field( wp_unslash( $_POST['notes'] ) ) : '';

		$roles = $this->shiftRoles();

		if ( $userId <= 0 || get_userdata( $userId ) === false ) {
			return array(
				'type'    => 'error',
				'message' => __( 'Selecciona un periodista válido.', 'dnorte-core' ),
			);
		}

		if ( ! array_key_exists( $role, $roles ) ) {
			return array(
				'type'    => 'error',
				'message' => __( 'Selecciona un rol de turno válido.', 'dnorte-core' ),
			);
		}

		$startsAt = $this->toMysqlDatetime( $starts );
		$endsAt   = $this->toMysqlDatetime( $ends );

		if ( $startsAt === null || $endsAt === null || $endsAt <= $startsAt ) {
			return array(
				'type'    => 'error',
				'message' => __( 'Revisa el inicio y el fin del turno: el fin debe ser posterior al inicio.', 'dnorte-core' ),
			);
		}

		$this->shifts->create( $userId, $role, $startsAt, $endsAt, $notes );

		return array(
			'type'    => 'success',
			'message' => __( 'Turno asignado.', 'dnorte-core' ),
		);
	}

	/**
	 * $_GET['id'] ya pasó por check_admin_referer() en handleRequest() antes de
	 * llegar aquí — ver la nota equivalente en handleCreate().
	 *
	 * @return array{type: string, message: string}
	 */
	private function handleDelete(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( $id > 0 ) {
			$this->shifts->delete( $id );
		}

		return array(
			'type'    => 'success',
			'message' => __( 'Turno eliminado.', 'dnorte-core' ),
		);
	}

	private function toMysqlDatetime( string $value ): ?string {
		if ( $value === '' ) {
			return null;
		}

		// Los <input type="datetime-local"> envían "YYYY-MM-DDTHH:MM"; MySQL espera
		// un espacio en vez de la "T".
		$normalized = str_replace( 'T', ' ', $value );
		$timestamp  = strtotime( $normalized );

		return $timestamp !== false ? gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
	}

	private function renderOnDutyNow(): void {
		$onDuty = $this->shifts->onDutyNow();

		echo '<h2>' . esc_html__( 'En turno ahora', 'dnorte-core' ) . '</h2>';

		if ( $onDuty === array() ) {
			echo '<p>' . esc_html__( 'Nadie tiene un turno activo en este momento.', 'dnorte-core' ) . '</p>';

			return;
		}

		echo '<ul class="dnorte-shifts-on-duty">';

		foreach ( $onDuty as $shift ) {
			$user = get_userdata( $shift->userId );

			printf(
				'<li><strong>%s</strong> — %s</li>',
				esc_html(
					$user !== false ? $user->display_name : sprintf(
					/* translators: %d: id del usuario. */
						__( 'Usuario #%d', 'dnorte-core' ),
						$shift->userId
					)
				),
				esc_html( $this->roleLabel( $shift->role ) )
			);
		}

		echo '</ul>';
	}

	private function renderForm(): void {
		echo '<h2>' . esc_html__( 'Asignar un turno', 'dnorte-core' ) . '</h2>';
		echo '<form method="post">';
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		echo '<input type="hidden" name="dnorte_shifts_action" value="create" />';

		echo '<table class="form-table"><tbody>';

		echo '<tr><th><label for="dnorte-shift-user">' . esc_html__( 'Periodista', 'dnorte-core' ) . '</label></th><td>';
		wp_dropdown_users(
			array(
				'id'                => 'dnorte-shift-user',
				'name'              => 'user_id',
				'show_option_none'  => __( '— Selecciona —', 'dnorte-core' ),
				'option_none_value' => '0',
			)
		);
		echo '</td></tr>';

		echo '<tr><th><label for="dnorte-shift-role">' . esc_html__( 'Rol de turno', 'dnorte-core' ) . '</label></th><td>';
		echo '<select id="dnorte-shift-role" name="role">';
		foreach ( $this->shiftRoles() as $key => $label ) {
			printf( '<option value="%s">%s</option>', esc_attr( $key ), esc_html( $label ) );
		}
		echo '</select></td></tr>';

		echo '<tr><th><label for="dnorte-shift-starts">' . esc_html__( 'Inicio', 'dnorte-core' ) . '</label></th><td>';
		echo '<input type="datetime-local" id="dnorte-shift-starts" name="starts_at" required="required" /></td></tr>';

		echo '<tr><th><label for="dnorte-shift-ends">' . esc_html__( 'Fin', 'dnorte-core' ) . '</label></th><td>';
		echo '<input type="datetime-local" id="dnorte-shift-ends" name="ends_at" required="required" /></td></tr>';

		echo '<tr><th><label for="dnorte-shift-notes">' . esc_html__( 'Notas', 'dnorte-core' ) . '</label></th><td>';
		echo '<input type="text" id="dnorte-shift-notes" name="notes" class="regular-text" /></td></tr>';

		echo '</tbody></table>';

		submit_button( __( 'Asignar turno', 'dnorte-core' ) );
		echo '</form>';
	}

	private function renderUpcomingTable(): void {
		$shifts = $this->shifts->upcoming();

		echo '<h2>' . esc_html__( 'Próximos turnos', 'dnorte-core' ) . '</h2>';

		if ( $shifts === array() ) {
			echo '<p>' . esc_html__( 'No hay turnos programados.', 'dnorte-core' ) . '</p>';

			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Periodista', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Rol', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Inicio', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Fin', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Notas', 'dnorte-core' ) . '</th>';
		echo '<th></th>';
		echo '</tr></thead><tbody>';

		foreach ( $shifts as $shift ) {
			$user      = get_userdata( $shift->userId );
			$deleteUrl = wp_nonce_url(
				add_query_arg(
					array(
						'page'                 => 'dnorte-turnos',
						'dnorte_shifts_action' => 'delete',
						'id'                   => $shift->id,
					),
					admin_url( 'admin.php' )
				),
				self::NONCE_ACTION
			);

			echo '<tr>';
			echo '<td>' . esc_html(
				$user !== false ? $user->display_name : sprintf(
				/* translators: %d: id del usuario. */
					__( 'Usuario #%d', 'dnorte-core' ),
					$shift->userId
				)
			) . '</td>';
			echo '<td>' . esc_html( $this->roleLabel( $shift->role ) ) . '</td>';
			echo '<td>' . esc_html( $this->displayDatetime( $shift->startsAt ) ) . '</td>';
			echo '<td>' . esc_html( $this->displayDatetime( $shift->endsAt ) ) . '</td>';
			echo '<td>' . esc_html( $shift->notes ) . '</td>';
			echo '<td><a href="' . esc_url( $deleteUrl ) . '" onclick="return confirm(\'' . esc_js( __( '¿Eliminar este turno?', 'dnorte-core' ) ) . '\')">' . esc_html__( 'Eliminar', 'dnorte-core' ) . '</a></td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	private function displayDatetime( string $mysqlDatetime ): string {
		$timestamp = strtotime( $mysqlDatetime . ' UTC' );

		return $timestamp !== false ? date_i18n( 'j M Y, H:i', $timestamp ) : $mysqlDatetime;
	}

	private function roleLabel( string $role ): string {
		$roles = $this->shiftRoles();

		return $roles[ $role ] ?? $role;
	}

	/**
	 * @return array<string, string>
	 */
	private function shiftRoles(): array {
		$roles = $this->config->get( 'workflow.shift_roles', array() );

		return is_array( $roles ) ? $roles : array();
	}
}
