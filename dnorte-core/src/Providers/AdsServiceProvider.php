<?php
/**
 * Conecta el módulo de publicidad propia: los dos espacios sitewide (cabecera/
 * inicio, hooks propios de dnorte-theme), los tres espacios de artículo (top
 * noticia/intermedio/final, todos vía el filtro `the_content` — ver
 * injectArticleAds()), los cargadores de Google AdSense/Ad Manager (una sola vez
 * por página cada uno, ver enqueueAdSenseLoader()/enqueueGamLoader()), el script
 * de seguimiento de impresiones/clics (ver renderTrackingScript()), el endpoint
 * REST que los recibe y el panel de administración.
 *
 * CampaignRepository se resuelve de forma diferida (dentro de cada callback, no
 * aquí en boot()), mismo motivo documentado en Search/AnalyticsServiceProvider:
 * depende en cadena de wpdb, inexistente en el proceso de pruebas unitarias.
 *
 * @package DNorteCore\Providers
 */

declare(strict_types=1);

namespace DNorteCore\Providers;

use DateTimeImmutable;
use DateTimeZone;
use DNorteCore\Admin\Contracts\RegistersAdminPages;
use DNorteCore\Ads\AdSlotRenderer;
use DNorteCore\Ads\AdsAdminPage;
use DNorteCore\Ads\Campaign;
use DNorteCore\Ads\CampaignEventController;
use DNorteCore\Ads\CampaignReportPdfRenderer;
use DNorteCore\Ads\CampaignRepository;
use DNorteCore\Ads\ContentParagraphInjector;
use DNorteCore\Config\Config;
use DNorteCore\Hooks\HookManager;
use DNorteCore\RestApi\Contracts\RegistersRoutes;

final class AdsServiceProvider extends ServiceProvider {

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		$hooks->addAction( 'dnorte_theme/before_topbar', $this->renderCabecera( ... ), 10 );
		$hooks->addAction( 'dnorte_theme/after_header', $this->renderInicio( ... ), 10 );
		$hooks->addAction( 'wp_enqueue_scripts', $this->enqueueAdSenseLoader( ... ), 10 );
		$hooks->addAction( 'wp_enqueue_scripts', $this->enqueueGamLoader( ... ), 10 );
		$hooks->addAction( 'wp_footer', $this->renderTrackingScript( ... ), 20 );
		$hooks->addAction( 'admin_init', $this->maybeDownloadReportPdf( ... ), 10 );
		$hooks->addFilter( 'the_content', $this->injectArticleAds( ... ), 20, 1 );
		$hooks->addFilter( 'dnorte_core/admin_pages', $this->addAdminPages( ... ), 10, 1 );
		$hooks->addFilter( 'dnorte_core/rest_controllers', $this->addRestControllers( ... ), 10, 1 );
	}

	public function renderCabecera(): void {
		echo $this->renderSlot( 'cabecera', array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- AdSlotRenderer::render() ya arma el HTML final (ver su propio phpcs:ignore documentado ahí).
	}

	public function renderInicio(): void {
		echo $this->renderSlot( 'inicio', array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ver renderCabecera().
	}

	/**
	 * Encola `adsbygoogle.js` una única vez por página (vía wp_enqueue_script(), no
	 * un <script> impreso a mano — así WordPress lo deduplica e imprime en el
	 * lugar correcto del <head> solo), solo si hay al menos una campaña de tipo
	 * AdSense activa ahora mismo — cada `<ins>` individual lo pone AdSlotRenderer,
	 * nunca repite este script. Simplificación deliberada de v0.1.0-alpha.13: usa
	 * el Client ID de la primera campaña AdSense activa que encuentra, sin
	 * comprobar si aparecerá de verdad en esta página concreta — correcto para un
	 * sitio con una sola cuenta de AdSense (el caso real), que es lo único que
	 * pedía el cliente. `crossorigin="anonymous"` (recomendado por Google, no
	 * imprescindible para que el anuncio funcione) queda fuera a propósito:
	 * añadirlo exigiría un filtro `script_loader_tag` aparte por un beneficio
	 * menor (mejores mensajes de error en la consola de Google, nada más).
	 */
	public function enqueueAdSenseLoader(): void {
		/** @var CampaignRepository $repository */
		$repository = $this->container->get( CampaignRepository::class );
		$now        = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		foreach ( $repository->all() as $campaign ) {
			if ( $campaign->type !== Campaign::TYPE_ADSENSE || $campaign->adsenseClientId === '' || ! $campaign->isActiveAt( $now ) ) {
				continue;
			}

			wp_enqueue_script(
				'dnorte-adsense',
				add_query_arg( 'client', $campaign->adsenseClientId, 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js' ),
				array(),
				null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- sin versión a propósito: es la URL exacta de un script de terceros (Google), añadirle "?ver=..." la cambiaría y WordPress no controla sus versiones.
				array( 'strategy' => 'async' )
			);

			return;
		}
	}

	/**
	 * Encola `gpt.js` (la librería de Google Ad Manager) una única vez por página,
	 * solo si hay al menos una campaña de tipo GAM activa ahora mismo — mismo
	 * criterio y motivo que enqueueAdSenseLoader().
	 */
	public function enqueueGamLoader(): void {
		/** @var CampaignRepository $repository */
		$repository = $this->container->get( CampaignRepository::class );
		$now        = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		foreach ( $repository->all() as $campaign ) {
			if ( $campaign->type !== Campaign::TYPE_GAM || $campaign->gamAdUnitPath === '' || ! $campaign->isActiveAt( $now ) ) {
				continue;
			}

			wp_enqueue_script(
				'dnorte-gpt',
				'https://securepubads.g.doubleclick.net/tag/js/gpt.js',
				array(),
				null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- sin versión a propósito, ver el mismo caso en enqueueAdSenseLoader().
				array( 'strategy' => 'async' )
			);

			return;
		}
	}

	public function injectArticleAds( string $content ): string {
		if ( ! $this->isArticleMainQueryContent() ) {
			return $content;
		}

		$postId   = get_the_ID();
		$rawSlugs = $postId !== false ? wp_get_post_categories( $postId, array( 'fields' => 'slugs' ) ) : array();
		/** @var list<string> $categorySlugs */
		$categorySlugs = is_array( $rawSlugs ) ? $rawSlugs : array();

		$top   = $this->renderSlot( 'top_noticia', $categorySlugs );
		$mid   = $this->renderSlot( 'intermedio', $categorySlugs );
		$final = $this->renderSlot( 'final', $categorySlugs );

		/** @var Config $config */
		$config = $this->container->get( Config::class );
		/** @var int $paragraph */
		$paragraph = $config->get( 'ads.mid_article_paragraph', 3 );

		$content = ( new ContentParagraphInjector() )->insertAfterParagraph( $top . $content, $mid, $paragraph );

		return $content . $final;
	}

	/**
	 * Script de seguimiento compartido (una sola vez por página, sin importar
	 * cuántos espacios con campaña rendericen) — impresión al cargar la página
	 * para cada `.dnorte-ad[data-campaign-id]` presente, clic delegado en
	 * `document` (funciona también para el HTML de una campaña insertado después,
	 * sin tener que enganchar un listener por elemento). Excluye al equipo
	 * editorial (`current_user_can('edit_posts')`) — mismo criterio que
	 * Analytics\PageviewBeaconRenderer, para no contaminar las estadísticas que
	 * alimentan "Generar informe" con las propias vistas previas del equipo.
	 * `navigator.sendBeacon()` en vez de `fetch()`: sigue enviándose aunque el
	 * clic navegue a otra página de inmediato.
	 */
	public function renderTrackingScript(): void {
		if ( current_user_can( 'edit_posts' ) ) {
			return;
		}

		$impressionUrl = esc_url_raw( rest_url( 'dnorte/v1/ads/impression' ) );
		$clickUrl      = esc_url_raw( rest_url( 'dnorte/v1/ads/click' ) );

		printf(
			'<script>(function(){if(!navigator.sendBeacon){return;}function post(u,id){navigator.sendBeacon(u,new Blob([JSON.stringify({campaign_id:parseInt(id,10)})],{type:"application/json"}));}var seen={};document.querySelectorAll(".dnorte-ad[data-campaign-id]").forEach(function(el){var id=el.getAttribute("data-campaign-id");if(seen[id]){return;}seen[id]=true;post(%s,id);});document.addEventListener("click",function(e){var el=e.target.closest(".dnorte-ad[data-campaign-id]");if(!el){return;}post(%s,el.getAttribute("data-campaign-id"));});})();</script>',
			wp_json_encode( $impressionUrl ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya son URLs propias escapadas por esc_url_raw()+wp_json_encode(); contexto JS, no HTML.
			wp_json_encode( $clickUrl )
		);
	}

	/**
	 * Descarga del PDF de "Generar informe" (AdsAdminPage::reportPdfUrl()).
	 * Enganchado a `admin_init` (no dentro de AdsAdminPage::render()) porque
	 * WordPress ya empezó a imprimir el HTML del panel de administración para
	 * cuando ese render() corre — enviar cabeceras `Content-Type: application/
	 * pdf` en ese punto ya es tarde. `admin_init` corre antes de cualquier
	 * salida de la página, así que puede interceptar la petición, generar el
	 * PDF y terminar la petición ahí mismo con `exit`.
	 */
	public function maybeDownloadReportPdf(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo lee (genera un PDF a partir de datos ya guardados), no escribe nada; protegido por manage_options más abajo.
		if ( ! isset( $_GET['page'], $_GET['pdf'] ) || sanitize_key( wp_unslash( $_GET['page'] ) ) !== 'dnorte-publicidad' ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permiso para descargar este informe.', 'dnorte-core' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ver el ignore de arriba.
		$id = absint( wp_unslash( $_GET['pdf'] ) );

		/** @var CampaignRepository $repository */
		$repository = $this->container->get( CampaignRepository::class );
		$campaign   = $repository->find( $id );

		if ( $campaign === null ) {
			wp_die( esc_html__( 'Campaña no encontrada.', 'dnorte-core' ) );
		}

		/** @var Config $config */
		$config = $this->container->get( Config::class );
		/** @var array<string, string> $slots */
		$slots     = $config->get( 'ads.slots', array() );
		$zoneNames = array_map( static fn ( string $z ): string => $slots[ $z ] ?? $z, $campaign->zones );

		$pdf = ( new CampaignReportPdfRenderer() )->render(
			$campaign,
			$zoneNames,
			AdsAdminPage::typeLabel( $campaign->type ),
			$campaign->enabled ? __( 'Activa', 'dnorte-core' ) : __( 'Inactiva', 'dnorte-core' )
		);

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="informe-' . sanitize_title( $campaign->name ) . '.pdf"' );
		header( 'Content-Length: ' . strlen( $pdf ) );
		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bytes binarios de un PDF, no marcado HTML.
		exit;
	}

	/**
	 * @param list<class-string<RegistersAdminPages>> $registrars
	 * @return list<class-string<RegistersAdminPages>>
	 */
	public function addAdminPages( array $registrars ): array {
		$registrars[] = AdsAdminPage::class;

		return $registrars;
	}

	/**
	 * @param list<class-string<RegistersRoutes>> $controllers
	 * @return list<class-string<RegistersRoutes>>
	 */
	public function addRestControllers( array $controllers ): array {
		$controllers[] = CampaignEventController::class;

		return $controllers;
	}

	/**
	 * @param list<string> $categorySlugs
	 */
	private function renderSlot( string $slotKey, array $categorySlugs ): string {
		/** @var CampaignRepository $repository */
		$repository = $this->container->get( CampaignRepository::class );
		$now        = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		$campaign = $repository->forZone( $slotKey, $now, $categorySlugs );

		return ( new AdSlotRenderer() )->render( $campaign, $slotKey );
	}

	/**
	 * Los tres espacios de artículo solo deben aparecer en el contenido real de un
	 * artículo publicado (no en un widget de "relacionados" que también llame a
	 * the_content(), ni en el bucle secundario de una consulta distinta).
	 */
	private function isArticleMainQueryContent(): bool {
		if ( ! in_the_loop() || ! is_main_query() ) {
			return false;
		}

		/** @var Config $config */
		$config = $this->container->get( Config::class );
		/** @var list<string> $postTypes */
		$postTypes = $config->get( 'ads.article_post_types', array( 'post' ) );

		return is_singular( $postTypes );
	}
}
