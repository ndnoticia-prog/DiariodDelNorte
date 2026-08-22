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

		// andReturnUsing (no justReturn): home_url() se llama con dos rutas
		// distintas en este test — justReturn() ignoraría el argumento y
		// devolvería siempre el mismo valor para ambas llamadas.
		Functions\when( 'home_url' )->alias(
			static fn ( string $path = '' ): string => 'https://diariodelnorte.net' . $path
		);
	}

	public function test_filter_appends_both_sitemap_directives_when_the_site_is_public(): void {
		$output = ( new RobotsTxtBuilder() )->filter( "User-agent: *\n", true );

		self::assertStringContainsString( 'Sitemap: https://diariodelnorte.net/wp-sitemap.xml', $output );
		self::assertStringContainsString( 'Sitemap: https://diariodelnorte.net/sitemap-news.xml', $output );
	}

	public function test_filter_leaves_a_private_site_untouched(): void {
		$output = ( new RobotsTxtBuilder() )->filter( "User-agent: *\nDisallow: /\n", false );

		self::assertSame( "User-agent: *\nDisallow: /\n", $output );
	}

	public function test_filter_does_not_duplicate_a_directive_already_present(): void {
		$original = "User-agent: *\nSitemap: https://diariodelnorte.net/wp-sitemap.xml\n";

		$output = ( new RobotsTxtBuilder() )->filter( $original, true );

		self::assertSame( 1, substr_count( $output, 'Sitemap: https://diariodelnorte.net/wp-sitemap.xml' ) );
		self::assertSame( 1, substr_count( $output, 'Sitemap: https://diariodelnorte.net/sitemap-news.xml' ) );
	}
}
