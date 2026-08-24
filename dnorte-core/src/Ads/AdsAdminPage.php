<?php
/**
 * Panel "Publicidad": lista de campañas existentes + un único formulario para
 * crear una campaña nueva o editar una ya creada (vía `?edit={id}`) — no un
 * formulario repetido por cada uno de los cinco espacios (ver el docblock de
 * Campaign sobre por qué una campaña ahora puede dirigirse a varios espacios a
 * la vez, con prioridad y segmentación por categoría). Rediseñado en
 * v0.1.0-alpha.13 a partir del formulario real de campañas del cliente.
 *
 * Capacidad `manage_options` — más estricta que el resto de paneles de la
 * plataforma (`edit_others_posts` en Turnos/Analítica) a propósito: este panel
 * guarda marcado sin filtrar (`<script>` de una red publicitaria, etc.), y esa
 * capacidad de inyectar HTML/JS sitewide debe quedar reservada a quien administra
 * el sitio, no a cualquier editor. Ver AdSlotRenderer para el criterio de
 * confianza equivalente a `unfiltered_html`.
 *
 * @package DNorteCore\Ads
 */

declare(strict_types=1);

namespace DNorteCore\Ads;

use DNorteCore\Admin\AdminPage;
use DNorteCore\Admin\Contracts\RegistersAdminPages;
use DNorteCore\Config\Config;
use DNorteCore\Support\DatetimeLocalInput;

final class AdsAdminPage implements RegistersAdminPages {

	private const CAPABILITY   = 'manage_options';
	private const NONCE_ACTION = 'dnorte_ads_manage';
	private const NONCE_FIELD  = 'dnorte_ads_nonce';
	private const TYPES        = array(
		Campaign::TYPE_HTML    => 'HTML/banner propio',
		Campaign::TYPE_ADSENSE => 'Google AdSense',
	);

	public function __construct(
		private readonly CampaignRepository $campaigns,
		private readonly Config $config
	) {
	}

	/**
	 * @return list<AdminPage>
	 */
	public function adminPages(): array {
		return array(
			new AdminPage(
				'dnorte-publicidad',
				__( 'Publicidad', 'dnorte-core' ),
				__( 'Publicidad', 'dnorte-core' ),
				self::CAPABILITY,
				$this->render( ... ),
				10,
				'dashicons-megaphone'
			),
		);
	}

	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'dnorte-core' ) );
		}

		$notice = $this->handleRequest();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Publicidad', 'dnorte-core' ) . '</h1>';

		if ( $notice !== null ) {
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $notice['type'] ),
				esc_html( $notice['message'] )
			);
		}

		$this->renderCampaignsTable();
		$this->renderForm( $this->campaignBeingEdited() );

		echo '</div>';
	}

	private function campaignBeingEdited(): ?Campaign {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo selecciona qué campaña precargar en el formulario, no escribe nada; el guardado real sí exige nonce (ver handleSave()).
		if ( ! isset( $_GET['edit'] ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ver el comentario de arriba.
		return $this->campaigns->find( absint( $_GET['edit'] ) );
	}

	/**
	 * @return array{type: string, message: string}|null
	 */
	private function handleRequest(): ?array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- la verificación real ocurre en check_admin_referer() más abajo; esta sola comprueba qué formulario se envió antes de tocar nada.
		if ( ! isset( $_POST['dnorte_ads_action'] ) && ! isset( $_GET['dnorte_ads_action'] ) ) {
			return null;
		}

		if ( isset( $_POST['dnorte_ads_action'] ) && sanitize_key( wp_unslash( $_POST['dnorte_ads_action'] ) ) === 'save' ) {
			check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

			return $this->handleSave();
		}

		if ( isset( $_GET['dnorte_ads_action'] ) && sanitize_key( wp_unslash( $_GET['dnorte_ads_action'] ) ) === 'delete' ) {
			check_admin_referer( self::NONCE_ACTION );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ya verificado dos líneas arriba.
			$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

			if ( $id > 0 ) {
				$this->campaigns->delete( $id );
			}

			return array(
				'type'    => 'success',
				'message' => __( 'Campaña eliminada.', 'dnorte-core' ),
			);
		}

		return null;
	}

	/**
	 * check_admin_referer() ya corrió en handleRequest() (el único método que llama
	 * a este) antes de llegar aquí. $_POST['html'] deliberadamente NO pasa por
	 * sanitize_textarea_field()/wp_kses_post(): es marcado de anuncio (una etiqueta
	 * <script> de una red publicitaria, casi siempre) que debe guardarse tal cual —
	 * ver el docblock de la clase sobre por qué esta página exige manage_options.
	 *
	 * @return array{type: string, message: string}
	 */
	private function handleSave(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$advertiser = isset( $_POST['advertiser'] ) ? sanitize_text_field( wp_unslash( $_POST['advertiser'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$rawType = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : Campaign::TYPE_HTML;
		$type    = array_key_exists( $rawType, self::TYPES ) ? $rawType : Campaign::TYPE_HTML;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$enabled = isset( $_POST['enabled'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$priority = isset( $_POST['priority'] ) ? intval( $_POST['priority'] ) : 0;
		$zones    = $this->readCheckedZones();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$categoriesRaw = isset( $_POST['categories'] ) ? sanitize_text_field( wp_unslash( $_POST['categories'] ) ) : '';
		$categories    = $this->splitCommaList( $categoriesRaw );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$starts = isset( $_POST['starts_at'] ) ? sanitize_text_field( wp_unslash( $_POST['starts_at'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$ends = isset( $_POST['ends_at'] ) ? sanitize_text_field( wp_unslash( $_POST['ends_at'] ) ) : '';
		/** @var string $html */
		$html = isset( $_POST['html'] ) ? wp_unslash( $_POST['html'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- ver el docblock de este método.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$adsenseClientId = isset( $_POST['adsense_client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['adsense_client_id'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$adsenseSlotId = isset( $_POST['adsense_slot_id'] ) ? sanitize_text_field( wp_unslash( $_POST['adsense_slot_id'] ) ) : '';

		if ( $name === '' ) {
			return array(
				'type'    => 'error',
				'message' => __( 'La campaña necesita un nombre.', 'dnorte-core' ),
			);
		}

		if ( $zones === array() ) {
			return array(
				'type'    => 'error',
				'message' => __( 'Selecciona al menos una zona.', 'dnorte-core' ),
			);
		}

		$campaign = new Campaign(
			$id,
			$name,
			$advertiser,
			$type,
			$enabled,
			$priority,
			$zones,
			$categories,
			DatetimeLocalInput::toMysqlDatetime( $starts ),
			DatetimeLocalInput::toMysqlDatetime( $ends ),
			$html,
			$adsenseClientId,
			$adsenseSlotId
		);

		$this->campaigns->save( $campaign );

		return array(
			'type'    => 'success',
			'message' => $id > 0 ? __( 'Campaña actualizada.', 'dnorte-core' ) : __( 'Campaña creada.', 'dnorte-core' ),
		);
	}

	/**
	 * @return list<string>
	 */
	private function readCheckedZones(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['zones'] ) || ! is_array( $_POST['zones'] ) ) {
			return array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$submitted = array_map( 'sanitize_key', wp_unslash( $_POST['zones'] ) );
		$valid     = array_keys( $this->slots() );

		return array_values( array_intersect( $submitted, $valid ) );
	}

	/**
	 * @return list<string>
	 */
	private function splitCommaList( string $value ): array {
		if ( trim( $value ) === '' ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'trim', explode( ',', $value ) ),
				static fn ( string $item ): bool => $item !== ''
			)
		);
	}

	private function renderCampaignsTable(): void {
		$campaigns = $this->campaigns->all();

		echo '<h2>' . esc_html__( 'Campañas', 'dnorte-core' ) . '</h2>';

		if ( $campaigns === array() ) {
			echo '<p>' . esc_html__( 'Todavía no hay ninguna campaña.', 'dnorte-core' ) . '</p>';

			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Nombre', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Anunciante', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Tipo', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Zonas', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Prioridad', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Activa', 'dnorte-core' ) . '</th>';
		echo '<th></th>';
		echo '</tr></thead><tbody>';

		foreach ( $campaigns as $campaign ) {
			$this->renderCampaignRow( $campaign );
		}

		echo '</tbody></table>';
	}

	private function renderCampaignRow( Campaign $campaign ): void {
		$slots     = $this->slots();
		$zoneNames = array_map(
			static fn ( string $zoneKey ): string => $slots[ $zoneKey ] ?? $zoneKey,
			$campaign->zones
		);

		$editUrl = add_query_arg(
			array(
				'page' => 'dnorte-publicidad',
				'edit' => $campaign->id,
			),
			admin_url( 'admin.php' )
		);

		$deleteUrl = wp_nonce_url(
			add_query_arg(
				array(
					'page'              => 'dnorte-publicidad',
					'dnorte_ads_action' => 'delete',
					'id'                => $campaign->id,
				),
				admin_url( 'admin.php' )
			),
			self::NONCE_ACTION
		);

		echo '<tr>';
		echo '<td><a href="' . esc_url( $editUrl ) . '">' . esc_html( $campaign->name ) . '</a></td>';
		echo '<td>' . esc_html( $campaign->advertiser ) . '</td>';
		echo '<td>' . esc_html( self::TYPES[ $campaign->type ] ?? $campaign->type ) . '</td>';
		echo '<td>' . esc_html( implode( ', ', $zoneNames ) ) . '</td>';
		echo '<td>' . esc_html( (string) $campaign->priority ) . '</td>';
		echo '<td>' . ( $campaign->enabled ? esc_html__( 'Sí', 'dnorte-core' ) : esc_html__( 'No', 'dnorte-core' ) ) . '</td>';
		echo '<td><a href="' . esc_url( $deleteUrl ) . '" onclick="return confirm(\'' . esc_js( __( '¿Eliminar esta campaña?', 'dnorte-core' ) ) . '\')">' . esc_html__( 'Eliminar', 'dnorte-core' ) . '</a></td>';
		echo '</tr>';
	}

	private function renderForm( ?Campaign $editing ): void {
		$heading = $editing !== null ? __( 'Editar campaña', 'dnorte-core' ) : __( 'Nueva campaña', 'dnorte-core' );

		echo '<h2>' . esc_html( $heading ) . '</h2>';
		echo '<form method="post">';
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		echo '<input type="hidden" name="dnorte_ads_action" value="save" />';
		echo '<input type="hidden" name="campaign_id" value="' . esc_attr( (string) ( $editing?->id ?? 0 ) ) . '" />';

		echo '<table class="form-table"><tbody>';

		$this->renderTextRow( 'name', __( 'Nombre', 'dnorte-core' ), $editing?->name ?? '', true );
		$this->renderTextRow( 'advertiser', __( 'Anunciante', 'dnorte-core' ), $editing?->advertiser ?? '' );
		$this->renderTypeRow( $editing?->type ?? Campaign::TYPE_HTML );
		$this->renderEnabledRow( $editing === null || $editing->enabled );
		$this->renderPriorityRow( $editing?->priority ?? 0 );
		$this->renderZonesRow( $editing?->zones ?? array() );
		$this->renderTextRow( 'categories', __( 'Categorías (opcional, separadas por coma; vacío = todas)', 'dnorte-core' ), implode( ', ', $editing?->categories ?? array() ) );
		$this->renderDatetimeRow( 'starts_at', __( 'Empieza (opcional)', 'dnorte-core' ), $editing?->startsAt ?? null );
		$this->renderDatetimeRow( 'ends_at', __( 'Termina (opcional)', 'dnorte-core' ), $editing?->endsAt ?? null );
		$this->renderHtmlRow( $editing?->html ?? '' );
		$this->renderTextRow( 'adsense_client_id', __( 'Client ID de AdSense (ca-pub-...)', 'dnorte-core' ), $editing?->adsenseClientId ?? '' );
		$this->renderTextRow( 'adsense_slot_id', __( 'Slot de AdSense', 'dnorte-core' ), $editing?->adsenseSlotId ?? '' );

		echo '</tbody></table>';

		submit_button(
			$editing !== null ? __( 'Actualizar campaña', 'dnorte-core' ) : __( 'Crear campaña', 'dnorte-core' ),
			'primary',
			'submit',
			false
		);

		if ( $editing !== null ) {
			$cancelUrl = remove_query_arg( 'edit' );
			echo ' <a class="button" href="' . esc_url( $cancelUrl ) . '">' . esc_html__( 'Cancelar', 'dnorte-core' ) . '</a>';
		}

		echo '</form>';
	}

	private function renderTextRow( string $name, string $label, string $value, bool $required = false ): void {
		$id = 'dnorte-ad-' . $name;

		echo '<tr><th><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="regular-text"' . ( $required ? ' required="required"' : '' ) . ' />';
		echo '</td></tr>';
	}

	private function renderTypeRow( string $selected ): void {
		echo '<tr><th><label for="dnorte-ad-type">' . esc_html__( 'Tipo', 'dnorte-core' ) . '</label></th><td>';
		echo '<select id="dnorte-ad-type" name="type">';

		foreach ( self::TYPES as $value => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				selected( $selected, $value, false ),
				esc_html( $label )
			);
		}

		echo '</select></td></tr>';
	}

	private function renderEnabledRow( bool $checked ): void {
		echo '<tr><th><label for="dnorte-ad-enabled">' . esc_html__( 'Activa', 'dnorte-core' ) . '</label></th><td>';
		echo '<input type="checkbox" id="dnorte-ad-enabled" name="enabled"' . checked( $checked, true, false ) . ' />';
		echo '</td></tr>';
	}

	private function renderPriorityRow( int $priority ): void {
		echo '<tr><th><label for="dnorte-ad-priority">' . esc_html__( 'Prioridad (más alto = más preferencia)', 'dnorte-core' ) . '</label></th><td>';
		echo '<input type="number" id="dnorte-ad-priority" name="priority" value="' . esc_attr( (string) $priority ) . '" class="small-text" />';
		echo '</td></tr>';
	}

	/**
	 * @param list<string> $selectedZones
	 */
	private function renderZonesRow( array $selectedZones ): void {
		echo '<tr><th>' . esc_html__( 'Zonas (dónde puede aparecer)', 'dnorte-core' ) . '</th><td>';
		echo '<fieldset>';

		foreach ( $this->slots() as $zoneKey => $label ) {
			$id = 'dnorte-ad-zone-' . $zoneKey;

			echo '<label for="' . esc_attr( $id ) . '" style="display:block;">';
			echo '<input type="checkbox" id="' . esc_attr( $id ) . '" name="zones[]" value="' . esc_attr( $zoneKey ) . '"' . checked( in_array( $zoneKey, $selectedZones, true ), true, false ) . ' /> ';
			echo esc_html( $label );
			echo '</label>';
		}

		echo '</fieldset>';
		echo '</td></tr>';
	}

	private function renderDatetimeRow( string $name, string $label, ?string $mysqlDatetime ): void {
		$id = 'dnorte-ad-' . $name;

		echo '<tr><th><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<input type="datetime-local" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $this->toDatetimeLocalValue( $mysqlDatetime ) ) . '" />';
		echo '</td></tr>';
	}

	private function renderHtmlRow( string $html ): void {
		echo '<tr><th><label for="dnorte-ad-html">' . esc_html__( 'HTML del anuncio (si el tipo es HTML/banner propio)', 'dnorte-core' ) . '</label></th><td>';
		echo '<textarea id="dnorte-ad-html" name="html" rows="5" class="large-text code">' . esc_textarea( $html ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'La etiqueta <script> de una red publicitaria, o un banner propio con <img>/<a>. Se ignora si el tipo es Google AdSense.', 'dnorte-core' ) . '</p>';
		echo '</td></tr>';
	}

	private function toDatetimeLocalValue( ?string $mysqlDatetime ): string {
		if ( $mysqlDatetime === null ) {
			return '';
		}

		$timestamp = strtotime( $mysqlDatetime . ' UTC' );

		return $timestamp !== false ? gmdate( 'Y-m-d\TH:i', $timestamp ) : '';
	}

	/**
	 * @return array<string, string>
	 */
	private function slots(): array {
		/** @var array<string, string> $slots */
		$slots = $this->config->get( 'ads.slots', array() );

		return $slots;
	}
}
