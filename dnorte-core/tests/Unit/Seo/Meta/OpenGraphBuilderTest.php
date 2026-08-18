<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Seo\Meta;

use DNorteCore\Seo\Context\SeoContext;
use DNorteCore\Seo\Meta\OpenGraphBuilder;
use DNorteCore\Tests\Unit\TestCase;

final class OpenGraphBuilderTest extends TestCase {

	public function test_build_includes_the_core_tags_always(): void {
		$context = new SeoContext( 'Título', '', 'https://example.test/post', false, 'article', null );

		$tags = ( new OpenGraphBuilder( 'Diario del Norte' ) )->build( $context );

		self::assertSame(
			array(
				'og:type'      => 'article',
				'og:title'     => 'Título',
				'og:url'       => 'https://example.test/post',
				'og:site_name' => 'Diario del Norte',
			),
			$tags
		);
	}

	public function test_build_adds_description_and_image_only_when_present(): void {
		$context = new SeoContext( 'T', 'Una descripción', 'https://example.test/', false, 'website', 'https://example.test/img.jpg' );

		$tags = ( new OpenGraphBuilder( 'Diario del Norte' ) )->build( $context );

		self::assertSame( 'Una descripción', $tags['og:description'] );
		self::assertSame( 'https://example.test/img.jpg', $tags['og:image'] );
	}

	public function test_build_omits_description_and_image_when_empty_or_null(): void {
		$context = new SeoContext( 'T', '', 'https://example.test/', false, 'website', null );

		$tags = ( new OpenGraphBuilder( 'Diario del Norte' ) )->build( $context );

		self::assertArrayNotHasKey( 'og:description', $tags );
		self::assertArrayNotHasKey( 'og:image', $tags );
	}
}
