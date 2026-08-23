<?php
/**
 * Panel "Publicidad": un formulario por cada uno de los cinco espacios de
 * `config/ads.php` (HTML del anuncio, activo/inactivo, ventana de fechas
 * opcional). Capacidad `manage_options` — más estricta que el resto de paneles de
 * la plataforma (`edit_others_posts` en Turnos/Analítica) a propósito: este panel
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

	public function __construct(
		private readonly AdRepository $ads,
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

		foreach ( $this->slots() as $slotKey => $label ) {
			$this->renderSlotSection( $slotKey, $label );
		}

		echo '</div>';
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

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- ya verificado dos líneas arriba.
			$slotKey = isset( $_POST['slot'] ) ? sanitize_key( wp_unslash( $_POST['slot'] ) ) : '';

			return $this->handleSave( $slotKey );
		}

		if ( isset( $_GET['dnorte_ads_action'] ) && sanitize_key( wp_unslash( $_GET['dnorte_ads_action'] ) ) === 'clear' ) {
			check_admin_referer( self::NONCE_ACTION );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ya verificado dos líneas arriba.
			$slotKey = isset( $_GET['slot'] ) ? sanitize_key( wp_unslash( $_GET['slot'] ) ) : '';

			return $this->handleClear( $slotKey );
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
	private function handleSave( string $slotKey ): array {
		if ( ! array_key_exists( $slotKey, $this->slots() ) ) {
			return array(
				'type'    => 'error',
				'message' => __( 'Espacio de publicidad inválido.', 'dnorte-core' ),
			);
		}

		/** @var string $html */
		$html = isset( $_POST['html'] ) ? wp_unslash( $_POST['html'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- ver el docblock de este método.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$enabled = isset( $_POST['enabled'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$starts = isset( $_POST['starts_at'] ) ? sanitize_text_field( wp_unslash( $_POST['starts_at'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$ends = isset( $_POST['ends_at'] ) ? sanitize_text_field( wp_unslash( $_POST['ends_at'] ) ) : '';

		$this->ads->upsert(
			$slotKey,
			$html,
			$enabled,
			DatetimeLocalInput::toMysqlDatetime( $starts ),
			DatetimeLocalInput::toMysqlDatetime( $ends )
		);

		return array(
			'type'    => 'success',
			'message' => __( 'Anuncio guardado.', 'dnorte-core' ),
		);
	}

	/**
	 * @return array{type: string, message: string}
	 */
	private function handleClear( string $slotKey ): array {
		if ( $slotKey !== '' ) {
			$this->ads->clear( $slotKey );
		}

		return array(
			'type'    => 'success',
			'message' => __( 'Anuncio eliminado.', 'dnorte-core' ),
		);
	}

	private function renderSlotSection( string $slotKey, string $label ): void {
		$ad = $this->ads->forSlot( $slotKey );

		echo '<h2>' . esc_html( $label ) . '</h2>';
		echo '<form method="post">';
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		echo '<input type="hidden" name="dnorte_ads_action" value="save" />';
		echo '<input type="hidden" name="slot" value="' . esc_attr( $slotKey ) . '" />';

		echo '<table class="form-table"><tbody>';

		echo '<tr><th><label for="dnorte-ad-html-' . esc_attr( $slotKey ) . '">' . esc_html__( 'HTML del anuncio', 'dnorte-core' ) . '</label></th><td>';
		echo '<textarea id="dnorte-ad-html-' . esc_attr( $slotKey ) . '" name="html" rows="5" class="large-text code">' . esc_textarea( $ad !== null ? $ad->html : '' ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Marcado del anuncio: la etiqueta <script> de una red publicitaria, o un banner propio con <img>/<a>.', 'dnorte-core' ) . '</p>';
		echo '</td></tr>';

		echo '<tr><th><label for="dnorte-ad-enabled-' . esc_attr( $slotKey ) . '">' . esc_html__( 'Activo', 'dnorte-core' ) . '</label></th><td>';
		echo '<input type="checkbox" id="dnorte-ad-enabled-' . esc_attr( $slotKey ) . '" name="enabled"' . ( $ad === null || $ad->enabled ? ' checked="checked"' : '' ) . ' />';
		echo '</td></tr>';

		echo '<tr><th><label for="dnorte-ad-starts-' . esc_attr( $slotKey ) . '">' . esc_html__( 'Empieza (opcional)', 'dnorte-core' ) . '</label></th><td>';
		echo '<input type="datetime-local" id="dnorte-ad-starts-' . esc_attr( $slotKey ) . '" name="starts_at" value="' . esc_attr( $this->toDatetimeLocalValue( $ad !== null ? $ad->startsAt : null ) ) . '" /></td></tr>';

		echo '<tr><th><label for="dnorte-ad-ends-' . esc_attr( $slotKey ) . '">' . esc_html__( 'Termina (opcional)', 'dnorte-core' ) . '</label></th><td>';
		echo '<input type="datetime-local" id="dnorte-ad-ends-' . esc_attr( $slotKey ) . '" name="ends_at" value="' . esc_attr( $this->toDatetimeLocalValue( $ad !== null ? $ad->endsAt : null ) ) . '" /></td></tr>';

		echo '</tbody></table>';

		submit_button( __( 'Guardar', 'dnorte-core' ) );
		echo '</form>';

		if ( $ad !== null ) {
			$clearUrl = wp_nonce_url(
				add_query_arg(
					array(
						'page'              => 'dnorte-publicidad',
						'dnorte_ads_action' => 'clear',
						'slot'              => $slotKey,
					),
					admin_url( 'admin.php' )
				),
				self::NONCE_ACTION
			);

			printf(
				'<p><a href="%s" onclick="return confirm(\'%s\')">%s</a></p>',
				esc_url( $clearUrl ),
				esc_js( __( '¿Eliminar este anuncio?', 'dnorte-core' ) ),
				esc_html__( 'Eliminar anuncio', 'dnorte-core' )
			);
		}

		echo '<hr />';
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
