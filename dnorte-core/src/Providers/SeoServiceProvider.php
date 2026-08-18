<?php
/**
 * Conecta el módulo SEO a WordPress: meta tags + Schema.org en wp_head (prioridad 1 y
 * 5 respectivamente, para que las meta tags salgan primero) y la directiva Sitemap:
 * en robots.txt.
 *
 * @package DNorteCore\Providers
 */

declare(strict_types=1);

namespace DNorteCore\Providers;

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

final class SeoServiceProvider extends ServiceProvider {

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		$hooks->addAction( 'wp_head', $this->renderMetaTags( ... ), 1 );
		$hooks->addAction( 'wp_head', $this->renderSchema( ... ), 5 );
		$hooks->addFilter( 'robots_txt', $this->filterRobotsTxt( ... ), 10, 2 );
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

	private function siteName(): string {
		return get_bloginfo( 'name' );
	}
}
