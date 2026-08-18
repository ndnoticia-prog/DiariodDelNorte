<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Seo\Robots;

use Brain\Monkey\Functions;
use DNorteCore\Seo\Robots\RobotsTxtBuilder;
use DNorteCore\Tests\Unit\TestCase;

final class RobotsTxtBuilderTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'home_url' )->justReturn( 'https://diariodelnorte.net/wp-sitemap.xml' );
	}

	public function test_filter_appends_the_sitemap_directive_when_the_site_is_public(): void {
		$output = ( new RobotsTxtBuilder() )->filter( "User-agent: *\n", true );

		self::assertStringContainsString( 'Sitemap: https://diariodelnorte.net/wp-sitemap.xml', $output );
	}

	public function test_filter_leaves_a_private_site_untouched(): void {
		$output = ( new RobotsTxtBuilder() )->filter( "User-agent: *\nDisallow: /\n", false );

		self::assertSame( "User-agent: *\nDisallow: /\n", $output );
	}

	public function test_filter_does_not_duplicate_the_directive_if_already_present(): void {
		$original = "User-agent: *\nSitemap: https://diariodelnorte.net/wp-sitemap.xml\n";

		$output = ( new RobotsTxtBuilder() )->filter( $original, true );

		self::assertSame( 1, substr_count( $output, 'Sitemap:' ) );
	}
}
