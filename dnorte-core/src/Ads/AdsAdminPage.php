<?php
/**
 * Panel "Publicidad": pestañas "Campañas" (tabla + un único formulario para crear
 * una campaña nueva o editar una vía `?edit={id}`, no un formulario repetido por
 * espacio) e "Historial" (bitácora de CampaignHistoryRepository) — más dos vistas
 * secundarias fuera de las pestañas: "Subir evidencia" (`?evidence={id}`, adjunta
 * capturas/comprobantes vía la Biblioteca de medios) y "Generar informe"
 * (`?report={id}`, resumen imprimible con estadísticas y evidencia). Rediseñado
 * en v0.1.0-alpha.14 a partir del panel de campañas real del cliente.
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
use WP_Error;

final class AdsAdminPage implements RegistersAdminPages {

	private const CAPABILITY    = 'manage_options';
	private const NONCE_ACTION  = 'dnorte_ads_manage';
	private const NONCE_FIELD   = 'dnorte_ads_nonce';
	private const TYPES         = array(
		Campaign::TYPE_HTML    => 'HTML/banner propio',
		Campaign::TYPE_ADSENSE => 'Google AdSense',
		Campaign::TYPE_IMAGE   => 'Imagen (banner propio)',
	);
	private const ACTION_LABELS = array(
		'creada'      => 'Creada',
		'actualizada' => 'Actualizada',
		'activada'    => 'Activada',
		'desactivada' => 'Desactivada',
		'borrada'     => 'Borrada',
		'evidencia'   => 'Evidencia subida',
	);

	public function __construct(
		private readonly CampaignRepository $campaigns,
		private readonly CampaignHistoryRepository $history,
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

		$this->renderStyles();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Publicidad', 'dnorte-core' );
		echo ' <a href="' . esc_url( $this->cleanBaseUrl() ) . '#dnorte-ad-form" class="page-title-action">' . esc_html__( 'Nueva campaña', 'dnorte-core' ) . '</a>';
		echo '</h1>';

		if ( $notice !== null ) {
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $notice['type'] ),
				esc_html( $notice['message'] )
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige qué vista mostrar, no escribe nada; el guardado real sí exige nonce (ver handleSave()/handleToggle()/handleUploadEvidence()).
		$reportId = isset( $_GET['report'] ) ? absint( $_GET['report'] ) : 0;
		if ( $reportId > 0 ) {
			$this->renderReportView( $reportId );
			echo '</div>';
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ver el comentario de arriba.
		$evidenceId = isset( $_GET['evidence'] ) ? absint( $_GET['evidence'] ) : 0;
		if ( $evidenceId > 0 ) {
			$this->renderEvidenceView( $evidenceId );
			echo '</div>';
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ver el comentario de arriba.
		$tab = isset( $_GET['tab'] ) && sanitize_key( wp_unslash( $_GET['tab'] ) ) === 'historial' ? 'historial' : 'campanas';
		$this->renderTabs( $tab );

		if ( $tab === 'historial' ) {
			$this->renderHistorial();
		} else {
			$this->renderCampaignsTable();
			$this->renderForm( $this->campaignBeingEdited() );
		}

		echo '</div>';
	}

	private function renderStyles(): void {
		echo '<style>
			.dnorte-ad-status { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
			.dnorte-ad-status--active { background: #edfaef; color: #1a7f37; }
			.dnorte-ad-status--inactive { background: #f6f7f7; color: #646970; }
			@media print {
				#adminmenumain, #wpadminbar, #wpfooter, .wrap > h1, .wrap > .notice, .dnorte-ad-report__back { display: none !important; }
				#wpcontent, #wpbody-content { margin: 0 !important; padding: 0 !important; }
			}
		</style>';
	}

	private function renderTabs( string $active ): void {
		$campaignsUrl = $this->cleanBaseUrl();
		$historialUrl = add_query_arg( 'tab', 'historial', $this->cleanBaseUrl() );

		echo '<h2 class="nav-tab-wrapper">';
		printf(
			'<a href="%s" class="nav-tab%s">%s</a>',
			esc_url( $campaignsUrl ),
			$active === 'campanas' ? ' nav-tab-active' : '',
			esc_html__( 'Campañas', 'dnorte-core' )
		);
		printf(
			'<a href="%s" class="nav-tab%s">%s</a>',
			esc_url( $historialUrl ),
			$active === 'historial' ? ' nav-tab-active' : '',
			esc_html__( 'Historial', 'dnorte-core' )
		);
		echo '</h2>';
	}

	/**
	 * URL de esta misma página sin ningún parámetro transitorio — ni de vista
	 * (`tab`/`edit`/`evidence`/`report`) ni de una acción de escritura ya
	 * ejecutada (`dnorte_ads_action`/`id`/`_wpnonce`). Hallazgo real de la
	 * verificación en el navegador: sin esto, los enlaces "Nueva campaña"/pestañas
	 * de una página cargada justo después de un "Activar"/"Desactivar"/"Borrar"
	 * seguían arrastrando esos tres parámetros de la URL actual — un clic
	 * posterior en cualquiera de ellos volvía a ejecutar la misma acción (con el
	 * mismo nonce, todavía válido) en vez de solo navegar.
	 */
	private function cleanBaseUrl(): string {
		return remove_query_arg( array( 'dnorte_ads_action', 'id', '_wpnonce', 'tab', 'edit', 'evidence', 'report' ) );
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

		if ( isset( $_POST['dnorte_ads_action'] ) ) {
			check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

			$postAction = sanitize_key( wp_unslash( $_POST['dnorte_ads_action'] ) );

			if ( $postAction === 'save' ) {
				return $this->handleSave();
			}

			if ( $postAction === 'upload_evidence' ) {
				return $this->handleUploadEvidence();
			}

			return null;
		}

		check_admin_referer( self::NONCE_ACTION );

		$getAction = sanitize_key( wp_unslash( $_GET['dnorte_ads_action'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ya verificado dos líneas arriba.
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( $getAction === 'delete' ) {
			return $this->handleDelete( $id );
		}

		if ( $getAction === 'toggle' ) {
			return $this->handleToggle( $id );
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
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$imageUrl = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$linkUrl = isset( $_POST['link_url'] ) ? esc_url_raw( wp_unslash( $_POST['link_url'] ) ) : '';

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

		$existing = $id > 0 ? $this->campaigns->find( $id ) : null;

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
			$adsenseSlotId,
			$imageUrl,
			$linkUrl
		);

		$savedId = $this->campaigns->save( $campaign );
		$this->history->record( $savedId, $name, $existing !== null ? 'actualizada' : 'creada', $this->currentActor() );

		return array(
			'type'    => 'success',
			'message' => $existing !== null ? __( 'Campaña actualizada.', 'dnorte-core' ) : __( 'Campaña creada.', 'dnorte-core' ),
		);
	}

	/**
	 * @return array{type: string, message: string}
	 */
	private function handleDelete( int $id ): array {
		if ( $id <= 0 ) {
			return array(
				'type'    => 'error',
				'message' => __( 'Campaña inválida.', 'dnorte-core' ),
			);
		}

		$campaign = $this->campaigns->find( $id );
		$this->campaigns->delete( $id );

		if ( $campaign !== null ) {
			$this->history->record( $id, $campaign->name, 'borrada', $this->currentActor() );
		}

		return array(
			'type'    => 'success',
			'message' => __( 'Campaña borrada.', 'dnorte-core' ),
		);
	}

	/**
	 * @return array{type: string, message: string}
	 */
	private function handleToggle( int $id ): array {
		$campaign = $this->campaigns->find( $id );

		if ( $campaign === null ) {
			return array(
				'type'    => 'error',
				'message' => __( 'Campaña no encontrada.', 'dnorte-core' ),
			);
		}

		$updated = new Campaign(
			$campaign->id,
			$campaign->name,
			$campaign->advertiser,
			$campaign->type,
			! $campaign->enabled,
			$campaign->priority,
			$campaign->zones,
			$campaign->categories,
			$campaign->startsAt,
			$campaign->endsAt,
			$campaign->html,
			$campaign->adsenseClientId,
			$campaign->adsenseSlotId,
			$campaign->imageUrl,
			$campaign->linkUrl
		);

		$this->campaigns->save( $updated );
		$this->history->record( $id, $campaign->name, $updated->enabled ? 'activada' : 'desactivada', $this->currentActor() );

		return array(
			'type'    => 'success',
			'message' => $updated->enabled ? __( 'Campaña activada.', 'dnorte-core' ) : __( 'Campaña desactivada.', 'dnorte-core' ),
		);
	}

	/**
	 * @return array{type: string, message: string}
	 */
	private function handleUploadEvidence(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$campaignId = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
		$campaign   = $this->campaigns->find( $campaignId );

		if ( $campaign === null ) {
			return array(
				'type'    => 'error',
				'message' => __( 'Campaña no encontrada.', 'dnorte-core' ),
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verificado en handleRequest() (el único método que llama a este) antes de llegar aquí.
		if ( ! isset( $_FILES['evidence_file'] ) || ! is_array( $_FILES['evidence_file'] ) || (int) ( $_FILES['evidence_file']['error'] ?? UPLOAD_ERR_NO_FILE ) === UPLOAD_ERR_NO_FILE ) {
			return array(
				'type'    => 'error',
				'message' => __( 'Selecciona un archivo antes de subir la evidencia.', 'dnorte-core' ),
			);
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachmentId = media_handle_upload( 'evidence_file', 0 );

		if ( $attachmentId instanceof WP_Error ) {
			return array(
				'type'    => 'error',
				'message' => $attachmentId->get_error_message(),
			);
		}

		$this->campaigns->addEvidence( $campaignId, $attachmentId );
		$this->history->record(
			$campaignId,
			$campaign->name,
			'evidencia',
			$this->currentActor(),
			sprintf(
				/* translators: %d: id del adjunto de la Biblioteca de medios. */
				__( 'Adjunto #%d', 'dnorte-core' ),
				$attachmentId
			)
		);

		return array(
			'type'    => 'success',
			'message' => __( 'Evidencia subida.', 'dnorte-core' ),
		);
	}

	private function currentActor(): string {
		$user = wp_get_current_user();

		return $user->display_name !== '' ? $user->display_name : $user->user_login;
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
		echo '<th>' . esc_html__( 'Estado', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Prioridad', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Estadísticas', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Acciones', 'dnorte-core' ) . '</th>';
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

		$editUrl     = add_query_arg(
			array(
				'page' => 'dnorte-publicidad',
				'edit' => $campaign->id,
			),
			admin_url( 'admin.php' )
		);
		$evidenceUrl = add_query_arg(
			array(
				'page'     => 'dnorte-publicidad',
				'evidence' => $campaign->id,
			),
			admin_url( 'admin.php' )
		);
		$reportUrl   = add_query_arg(
			array(
				'page'   => 'dnorte-publicidad',
				'report' => $campaign->id,
			),
			admin_url( 'admin.php' )
		);

		$toggleUrl = wp_nonce_url(
			add_query_arg(
				array(
					'page'              => 'dnorte-publicidad',
					'dnorte_ads_action' => 'toggle',
					'id'                => $campaign->id,
				),
				admin_url( 'admin.php' )
			),
			self::NONCE_ACTION
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
		echo '<td><a href="' . esc_url( $editUrl ) . '"><strong>' . esc_html( $campaign->name ) . '</strong></a>';
		if ( $zoneNames !== array() ) {
			echo '<br /><span class="description">' . esc_html( implode( ', ', $zoneNames ) ) . '</span>';
		}
		echo '</td>';
		echo '<td>' . esc_html( $campaign->advertiser ) . '</td>';
		echo '<td>' . esc_html( self::TYPES[ $campaign->type ] ?? $campaign->type ) . '</td>';
		echo '<td>' . $this->statusPill( $campaign->enabled ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- statusPill() ya escapa su único contenido dinámico (la etiqueta traducida); el resto es marcado fijo.
		echo '<td>' . esc_html( (string) $campaign->priority ) . '</td>';
		echo '<td>' . esc_html( $this->statsLabel( $campaign ) ) . '</td>';
		echo '<td>';
		printf( '<a href="%s">%s</a> · ', esc_url( $editUrl ), esc_html__( 'Editar', 'dnorte-core' ) );
		printf(
			'<a href="%s">%s</a> · ',
			esc_url( $toggleUrl ),
			$campaign->enabled ? esc_html__( 'Desactivar', 'dnorte-core' ) : esc_html__( 'Activar', 'dnorte-core' )
		);
		printf( '<a href="%s" class="button button-primary button-small">%s</a> ', esc_url( $evidenceUrl ), esc_html__( 'Subir evidencia', 'dnorte-core' ) );
		printf( '<a href="%s">%s</a> · ', esc_url( $reportUrl ), esc_html__( 'Generar informe', 'dnorte-core' ) );
		printf(
			'<a href="%s" onclick="return confirm(\'%s\')">%s</a>',
			esc_url( $deleteUrl ),
			esc_js( __( '¿Borrar esta campaña?', 'dnorte-core' ) ),
			esc_html__( 'Borrar', 'dnorte-core' )
		);
		echo '</td>';
		echo '</tr>';
	}

	private function statusPill( bool $enabled ): string {
		return sprintf(
			'<span class="dnorte-ad-status dnorte-ad-status--%s">%s</span>',
			$enabled ? 'active' : 'inactive',
			esc_html( $enabled ? __( 'Activa', 'dnorte-core' ) : __( 'Inactiva', 'dnorte-core' ) )
		);
	}

	private function statsLabel( Campaign $campaign ): string {
		return sprintf(
			/* translators: 1: número de impresiones, 2: número de clics, 3: porcentaje de CTR con dos decimales. */
			__( '%1$s impr. · %2$s clics · %3$s%% CTR', 'dnorte-core' ),
			number_format_i18n( $campaign->impressions ),
			number_format_i18n( $campaign->clicks ),
			number_format_i18n( $campaign->ctr(), 2 )
		);
	}

	private function renderHistorial(): void {
		$entries = $this->history->recent( 100 );

		echo '<h2>' . esc_html__( 'Historial', 'dnorte-core' ) . '</h2>';

		if ( $entries === array() ) {
			echo '<p>' . esc_html__( 'Todavía no hay actividad registrada.', 'dnorte-core' ) . '</p>';

			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Fecha', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Campaña', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Acción', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Usuario', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Detalles', 'dnorte-core' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $entries as $entry ) {
			echo '<tr>';
			echo '<td>' . esc_html( $this->displayDatetime( $entry->createdAt ) ) . '</td>';
			echo '<td>' . esc_html( $entry->campaignName ) . '</td>';
			echo '<td>' . esc_html( self::ACTION_LABELS[ $entry->action ] ?? $entry->action ) . '</td>';
			echo '<td>' . esc_html( $entry->actor ) . '</td>';
			echo '<td>' . esc_html( $entry->details ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	private function renderEvidenceView( int $campaignId ): void {
		$campaign = $this->campaigns->find( $campaignId );
		$backUrl  = $this->cleanBaseUrl();

		echo '<p><a href="' . esc_url( $backUrl ) . '">&larr; ' . esc_html__( 'Volver a campañas', 'dnorte-core' ) . '</a></p>';

		if ( $campaign === null ) {
			echo '<p>' . esc_html__( 'Campaña no encontrada.', 'dnorte-core' ) . '</p>';

			return;
		}

		echo '<h2>';
		printf(
			/* translators: %s: nombre de la campaña. */
			esc_html__( 'Evidencia de "%s"', 'dnorte-core' ),
			esc_html( $campaign->name )
		);
		echo '</h2>';

		echo '<form method="post" enctype="multipart/form-data">';
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		echo '<input type="hidden" name="dnorte_ads_action" value="upload_evidence" />';
		echo '<input type="hidden" name="campaign_id" value="' . esc_attr( (string) $campaignId ) . '" />';
		echo '<input type="file" name="evidence_file" accept="image/*,.pdf" /> ';
		submit_button( __( 'Subir evidencia', 'dnorte-core' ), 'primary', 'submit', false );
		echo '</form>';

		if ( $campaign->evidenceIds === array() ) {
			echo '<p>' . esc_html__( 'Todavía no hay evidencia subida para esta campaña.', 'dnorte-core' ) . '</p>';

			return;
		}

		echo '<h3>' . esc_html__( 'Archivos subidos', 'dnorte-core' ) . '</h3><ul>';

		foreach ( $campaign->evidenceIds as $attachmentId ) {
			$url = wp_get_attachment_url( $attachmentId );

			if ( $url === false ) {
				continue;
			}

			$title = get_the_title( $attachmentId );
			printf(
				'<li><a href="%s" target="_blank">%s</a></li>',
				esc_url( $url ),
				esc_html( $title !== '' ? $title : $url )
			);
		}

		echo '</ul>';
	}

	private function renderReportView( int $campaignId ): void {
		$campaign = $this->campaigns->find( $campaignId );
		$backUrl  = $this->cleanBaseUrl();

		echo '<div class="dnorte-ad-report">';
		echo '<p class="dnorte-ad-report__back"><a href="' . esc_url( $backUrl ) . '">&larr; ' . esc_html__( 'Volver a campañas', 'dnorte-core' ) . '</a>';
		echo ' &middot; <a href="#" onclick="window.print();return false;">' . esc_html__( 'Imprimir / Guardar como PDF', 'dnorte-core' ) . '</a></p>';

		if ( $campaign === null ) {
			echo '<p>' . esc_html__( 'Campaña no encontrada.', 'dnorte-core' ) . '</p>';
			echo '</div>';

			return;
		}

		$slots     = $this->slots();
		$zoneNames = array_map( static fn ( string $z ): string => $slots[ $z ] ?? $z, $campaign->zones );

		echo '<h1>' . esc_html__( 'Informe de campaña', 'dnorte-core' ) . '</h1>';
		echo '<table class="widefat"><tbody>';
		$this->renderReportRow( __( 'Nombre', 'dnorte-core' ), $campaign->name );
		$this->renderReportRow( __( 'Anunciante', 'dnorte-core' ), $campaign->advertiser );
		$this->renderReportRow( __( 'Tipo', 'dnorte-core' ), self::TYPES[ $campaign->type ] ?? $campaign->type );
		$this->renderReportRow( __( 'Estado', 'dnorte-core' ), $campaign->enabled ? __( 'Activa', 'dnorte-core' ) : __( 'Inactiva', 'dnorte-core' ) );
		$this->renderReportRow( __( 'Zonas', 'dnorte-core' ), implode( ', ', $zoneNames ) );
		$this->renderReportRow( __( 'Empieza', 'dnorte-core' ), $campaign->startsAt !== null ? $this->displayDatetime( $campaign->startsAt ) : __( 'Sin definir', 'dnorte-core' ) );
		$this->renderReportRow( __( 'Termina', 'dnorte-core' ), $campaign->endsAt !== null ? $this->displayDatetime( $campaign->endsAt ) : __( 'Sin definir', 'dnorte-core' ) );
		$this->renderReportRow( __( 'Impresiones', 'dnorte-core' ), number_format_i18n( $campaign->impressions ) );
		$this->renderReportRow( __( 'Clics', 'dnorte-core' ), number_format_i18n( $campaign->clicks ) );
		$this->renderReportRow( __( 'CTR', 'dnorte-core' ), number_format_i18n( $campaign->ctr(), 2 ) . '%' );
		echo '</tbody></table>';

		if ( $campaign->evidenceIds !== array() ) {
			echo '<h2>' . esc_html__( 'Evidencia', 'dnorte-core' ) . '</h2><ul>';

			foreach ( $campaign->evidenceIds as $attachmentId ) {
				$url = wp_get_attachment_url( $attachmentId );

				if ( $url === false ) {
					continue;
				}

				printf( '<li><a href="%s" target="_blank">%s</a></li>', esc_url( $url ), esc_html( $url ) );
			}

			echo '</ul>';
		}

		printf(
			'<p>%s</p>',
			esc_html(
				sprintf(
					/* translators: %s: fecha y hora de generación del informe. */
					__( 'Generado el %s', 'dnorte-core' ),
					date_i18n( 'j \d\e F \d\e Y, H:i' )
				)
			)
		);

		echo '</div>';
	}

	private function renderReportRow( string $label, string $value ): void {
		echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
	}

	private function renderForm( ?Campaign $editing ): void {
		$heading = $editing !== null ? __( 'Editar campaña', 'dnorte-core' ) : __( 'Nueva campaña', 'dnorte-core' );

		echo '<h2>' . esc_html( $heading ) . '</h2>';
		echo '<form method="post" id="dnorte-ad-form">';
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
		$this->renderTextRow( 'image_url', __( 'URL de la imagen (si el tipo es Imagen)', 'dnorte-core' ), $editing?->imageUrl ?? '' );
		$this->renderTextRow( 'link_url', __( 'URL de destino (si el tipo es Imagen)', 'dnorte-core' ), $editing?->linkUrl ?? '' );
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
			echo ' <a class="button" href="' . esc_url( $this->cleanBaseUrl() ) . '">' . esc_html__( 'Cancelar', 'dnorte-core' ) . '</a>';
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
		echo '<p class="description">' . esc_html__( 'La etiqueta <script> de una red publicitaria, o un banner propio con <img>/<a>. Se ignora si el tipo es Imagen o Google AdSense.', 'dnorte-core' ) . '</p>';
		echo '</td></tr>';
	}

	private function toDatetimeLocalValue( ?string $mysqlDatetime ): string {
		if ( $mysqlDatetime === null ) {
			return '';
		}

		$timestamp = strtotime( $mysqlDatetime . ' UTC' );

		return $timestamp !== false ? gmdate( 'Y-m-d\TH:i', $timestamp ) : '';
	}

	private function displayDatetime( string $mysqlDatetime ): string {
		$timestamp = strtotime( $mysqlDatetime . ' UTC' );

		return $timestamp !== false ? date_i18n( 'j M Y, H:i', $timestamp ) : $mysqlDatetime;
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
