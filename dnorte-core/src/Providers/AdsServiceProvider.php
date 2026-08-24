<?php
/**
 * Conecta el módulo de publicidad propia: los dos espacios sitewide (cabecera/
 * inicio, hooks propios de dnorte-theme), los tres espacios de artículo (top
 * noticia/intermedio/final, todos vía el filtro `the_content` — ver
 * injectArticleAds()), el cargador de Google AdSense (una sola vez por página,
 * ver enqueueAdSenseLoader()) y el panel de administración.
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
use DNorteCore\Ads\CampaignRepository;
use DNorteCore\Ads\ContentParagraphInjector;
use DNorteCore\Config\Config;
use DNorteCore\Hooks\HookManager;

final class AdsServiceProvider extends ServiceProvider {

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		$hooks->addAction( 'dnorte_theme/before_topbar', $this->renderCabecera( ... ), 10 );
		$hooks->addAction( 'dnorte_theme/after_header', $this->renderInicio( ... ), 10 );
		$hooks->addAction( 'wp_enqueue_scripts', $this->enqueueAdSenseLoader( ... ), 10 );
		$hooks->addFilter( 'the_content', $this->injectArticleAds( ... ), 20, 1 );
		$hooks->addFilter( 'dnorte_core/admin_pages', $this->addAdminPages( ... ), 10, 1 );
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
	 * @param list<class-string<RegistersAdminPages>> $registrars
	 * @return list<class-string<RegistersAdminPages>>
	 */
	public function addAdminPages( array $registrars ): array {
		$registrars[] = AdsAdminPage::class;

		return $registrars;
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
