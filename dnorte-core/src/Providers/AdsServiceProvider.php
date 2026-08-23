<?php
/**
 * Conecta el módulo de publicidad propia: los dos espacios sitewide (cabecera/
 * inicio, hooks propios de dnorte-theme) y los tres espacios de artículo (top
 * noticia/intermedio/final, todos vía el filtro `the_content` — ver
 * injectArticleAds()) y el panel de administración.
 *
 * AdRepository se resuelve de forma diferida (dentro de cada callback, no aquí en
 * boot()), mismo motivo documentado en Search/AnalyticsServiceProvider: depende en
 * cadena de wpdb, inexistente en el proceso de pruebas unitarias.
 *
 * @package DNorteCore\Providers
 */

declare(strict_types=1);

namespace DNorteCore\Providers;

use DateTimeImmutable;
use DateTimeZone;
use DNorteCore\Admin\Contracts\RegistersAdminPages;
use DNorteCore\Ads\AdRepository;
use DNorteCore\Ads\AdSlotRenderer;
use DNorteCore\Ads\AdsAdminPage;
use DNorteCore\Ads\ContentParagraphInjector;
use DNorteCore\Config\Config;
use DNorteCore\Hooks\HookManager;

final class AdsServiceProvider extends ServiceProvider {

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		$hooks->addAction( 'dnorte_theme/before_topbar', $this->renderCabecera( ... ), 10 );
		$hooks->addAction( 'dnorte_theme/after_header', $this->renderInicio( ... ), 10 );
		$hooks->addFilter( 'the_content', $this->injectArticleAds( ... ), 20, 1 );
		$hooks->addFilter( 'dnorte_core/admin_pages', $this->addAdminPages( ... ), 10, 1 );
	}

	public function renderCabecera(): void {
		echo $this->renderSlot( 'cabecera' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- AdSlotRenderer::render() ya arma el HTML final (ver su propio phpcs:ignore documentado ahí).
	}

	public function renderInicio(): void {
		echo $this->renderSlot( 'inicio' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ver renderCabecera().
	}

	public function injectArticleAds( string $content ): string {
		if ( ! $this->isArticleMainQueryContent() ) {
			return $content;
		}

		$now      = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$renderer = new AdSlotRenderer();
		/** @var AdRepository $repository */
		$repository = $this->container->get( AdRepository::class );

		$top   = $renderer->render( $repository->forSlot( 'top_noticia' ), 'top_noticia', $now );
		$mid   = $renderer->render( $repository->forSlot( 'intermedio' ), 'intermedio', $now );
		$final = $renderer->render( $repository->forSlot( 'final' ), 'final', $now );

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

	private function renderSlot( string $slotKey ): string {
		/** @var AdRepository $repository */
		$repository = $this->container->get( AdRepository::class );

		return ( new AdSlotRenderer() )->render(
			$repository->forSlot( $slotKey ),
			$slotKey,
			new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) )
		);
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
