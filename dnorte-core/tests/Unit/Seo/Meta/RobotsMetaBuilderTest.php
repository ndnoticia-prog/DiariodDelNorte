<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Seo\Meta;

use DNorteCore\Seo\Context\SeoContext;
use DNorteCore\Seo\Meta\RobotsMetaBuilder;
use DNorteCore\Tests\Unit\TestCase;

final class RobotsMetaBuilderTest extends TestCase {

	public function test_build_returns_noindex_nofollow_when_context_says_noindex(): void {
		$context = new SeoContext( 'T', 'D', 'https://example.test/', true, 'website', null );

		self::assertSame( 'noindex, nofollow', ( new RobotsMetaBuilder() )->build( $context ) );
	}

	public function test_build_returns_index_follow_with_discover_eligibility_otherwise(): void {
		$context = new SeoContext( 'T', 'D', 'https://example.test/', false, 'website', null );

		self::assertSame( 'index, follow, max-image-preview:large', ( new RobotsMetaBuilder() )->build( $context ) );
	}
}
