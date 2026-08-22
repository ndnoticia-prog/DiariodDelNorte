<?php
/**
 * Conecta el módulo SEO a WordPress: meta tags + Schema.org en wp_head (prioridad 1 y
 * 5 respectivamente, para que las meta tags salgan primero), la directiva Sitemap:
 * en robots.txt, y el sitemap de Google News (/sitemap-news.xml).
 *
 * @package DNorteCore\Providers
 */

declare(strict_types=1);

namespace DNorteCore\Providers;

use DNorteCore\Config\Config;
use DNorteCore\Hooks\HookManager;
use DNorteCore\Seo\Breadcrumbs\BreadcrumbBuilder;
use DNorteCore\Seo\Context\SeoContextResolver;
use DNorteCore\Seo\Meta\MetaTagsRenderer;
use DNorteCore\Seo\Meta\OpenGraphBuilder;
use DNorteCore\Seo\Meta\RobotsMetaBuilder;
use DNorteCore\Seo\Meta\TwitterCardBuilder;
use DNorteCore\Seo\Robots\RobotsTxtBuilder;
use DNorteCore\Seo\Schema\ArticleSchema;
use DNorteCore\Seo\Schema\BreadcrumbListSchema;
use DNorteCore\Seo\Schema\OrganizationSchema;
use DNorteCore\Seo\Schema\SchemaOutput;
use DNorteCore\Seo\Schema\WebSiteSchema;
use DNorteCore\Seo\Sitemap\NewsSitemapController;
use WP_Query;

final class SeoServiceProvider extends ServiceProvider {

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		$hooks->addAction( 'wp_head', $this->renderMetaTags( ... ), 1 );
		$hooks->addAction( 'wp_head', $this->renderSchema( ... ), 5 );
		$hooks->addFilter( 'robots_txt', $this->filterRobotsTxt( ... ), 10, 2 );

		$hooks->addAction( 'init', $this->registerNewsSitemapRewrite( ... ), 10 );
		$hooks->addFilter( 'query_vars', $this->addNewsSitemapQueryVar( ... ), 10, 1 );
		$hooks->addAction( 'parse_query', $this->maybeRenderNewsSitemap( ... ), 1, 1 );
	}

	public function renderMetaTags(): void {
		$context = ( new SeoContextResolver() )->resolve();

		$renderer = new MetaTagsRenderer(
			new RobotsMetaBuilder(),
			new OpenGraphBuilder( $this->siteName() ),
			new TwitterCardBuilder()
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- MetaTagsRenderer ya escapa cada valor individual (esc_attr/esc_url) antes de ensamblar el HTML.
		echo $renderer->render( $context );
	}

	public function renderSchema(): void {
		$context = ( new SeoContextResolver() )->resolve();
		$siteUrl = home_url( '/' );

		$nodes = array(
			( new OrganizationSchema( $this->siteName(), $siteUrl ) )->build(),
			( new WebSiteSchema( $this->siteName(), $siteUrl ) )->build(),
		);

		if ( $context->isArticle() && $context->post !== null ) {
			$nodes[] = ( new ArticleSchema( $siteUrl ) )->build( $context->post );
		}

		$breadcrumbItems = ( new BreadcrumbBuilder( $this->siteName(), $siteUrl ) )->build();

		if ( count( $breadcrumbItems ) > 1 ) {
			$nodes[] = ( new BreadcrumbListSchema() )->build( $breadcrumbItems );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SchemaOutput ya codifica con wp_json_encode(JSON_HEX_TAG|JSON_HEX_AMP), no HTML sin escapar.
		echo ( new SchemaOutput() )->toScript( $nodes );
	}

	public function filterRobotsTxt( string $output, bool $public ): string {
		return ( new RobotsTxtBuilder() )->filter( $output, $public );
	}

	public function registerNewsSitemapRewrite(): void {
		$this->newsSitemapController()->registerRewriteRule();
	}

	/**
	 * @param list<string> $vars
	 * @return list<string>
	 */
	public function addNewsSitemapQueryVar( array $vars ): array {
		return $this->newsSitemapController()->registerQueryVar( $vars );
	}

	public function maybeRenderNewsSitemap( WP_Query $query ): void {
		$this->newsSitemapController()->maybeRender( $query );
	}

	private function newsSitemapController(): NewsSitemapController {
		/** @var Config $config */
		$config = $this->container->get( Config::class );

		$language    = $config->get( 'seo.news_sitemap.language', 'es' );
		$windowHours = $config->get( 'seo.news_sitemap.window_hours', 48 );

		return new NewsSitemapController(
			$this->siteName(),
			is_string( $language ) ? $language : 'es',
			is_int( $windowHours ) ? $windowHours : 48
		);
	}

	private function siteName(): string {
		return get_bloginfo( 'name' );
	}
}
