<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Seo\Meta;

use DNorteCore\Seo\Context\SeoContext;
use DNorteCore\Seo\Meta\TwitterCardBuilder;
use DNorteCore\Tests\Unit\TestCase;

final class TwitterCardBuilderTest extends TestCase {

	public function test_build_uses_summary_large_image_when_an_image_is_present(): void {
		$context = new SeoContext( 'T', 'D', 'https://example.test/', false, 'article', 'https://example.test/img.jpg' );

		$tags = ( new TwitterCardBuilder() )->build( $context );

		self::assertSame( 'summary_large_image', $tags['twitter:card'] );
		self::assertSame( 'https://example.test/img.jpg', $tags['twitter:image'] );
	}

	public function test_build_falls_back_to_summary_without_an_image(): void {
		$context = new SeoContext( 'T', 'D', 'https://example.test/', false, 'website', null );

		$tags = ( new TwitterCardBuilder() )->build( $context );

		self::assertSame( 'summary', $tags['twitter:card'] );
		self::assertArrayNotHasKey( 'twitter:image', $tags );
	}

	public function test_build_omits_description_when_empty(): void {
		$context = new SeoContext( 'T', '', 'https://example.test/', false, 'website', null );

		$tags = ( new TwitterCardBuilder() )->build( $context );

		self::assertArrayNotHasKey( 'twitter:description', $tags );
	}
}
