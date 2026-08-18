<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Seo\Meta;

use Brain\Monkey\Functions;
use DNorteCore\Seo\Context\SeoContext;
use DNorteCore\Seo\Meta\MetaTagsRenderer;
use DNorteCore\Seo\Meta\OpenGraphBuilder;
use DNorteCore\Seo\Meta\RobotsMetaBuilder;
use DNorteCore\Seo\Meta\TwitterCardBuilder;
use DNorteCore\Tests\Unit\TestCase;

final class MetaTagsRendererTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
	}

	public function test_render_includes_robots_canonical_og_and_twitter_tags(): void {
		$context = new SeoContext(
			'Título del artículo',
			'Una descripción',
			'https://example.test/post',
			false,
			'article',
			'https://example.test/img.jpg'
		);

		$renderer = new MetaTagsRenderer( new RobotsMetaBuilder(), new OpenGraphBuilder( 'Diario del Norte' ), new TwitterCardBuilder() );

		$html = $renderer->render( $context );

		self::assertStringContainsString( '<meta name="robots" content="index, follow, max-image-preview:large" />', $html );
		self::assertStringContainsString( '<link rel="canonical" href="https://example.test/post" />', $html );
		self::assertStringContainsString( 'property="og:title" content="Título del artículo"', $html );
		self::assertStringContainsString( 'name="twitter:card" content="summary_large_image"', $html );
	}

	public function test_render_reflects_noindex_from_the_context(): void {
		$context = new SeoContext( 'T', '', 'https://example.test/buscar', true, 'website', null );

		$renderer = new MetaTagsRenderer( new RobotsMetaBuilder(), new OpenGraphBuilder( 'Diario del Norte' ), new TwitterCardBuilder() );

		$html = $renderer->render( $context );

		self::assertStringContainsString( '<meta name="robots" content="noindex, nofollow" />', $html );
	}
}
