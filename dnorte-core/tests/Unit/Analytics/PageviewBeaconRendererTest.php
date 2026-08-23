<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Analytics;

use Brain\Monkey\Functions;
use DNorteCore\Analytics\PageviewBeaconRenderer;
use DNorteCore\Config\Config;
use DNorteCore\Tests\Unit\TestCase;

final class PageviewBeaconRendererTest extends TestCase {

	public function test_it_prints_nothing_for_a_user_who_can_edit_posts(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$this->expectOutputString( '' );

		( new PageviewBeaconRenderer( new Config() ) )->render();
	}

	public function test_it_prints_nothing_outside_a_tracked_post_type(): void {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'is_singular' )->justReturn( false );

		$this->expectOutputString( '' );

		( new PageviewBeaconRenderer( new Config( array( 'analytics' => array( 'tracked_post_types' => array( 'post' ) ) ) ) ) )->render();
	}

	public function test_it_prints_nothing_without_a_queried_object(): void {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_queried_object_id' )->justReturn( 0 );

		$this->expectOutputString( '' );

		( new PageviewBeaconRenderer( new Config() ) )->render();
	}

	public function test_it_prints_a_beacon_script_with_the_post_id_and_endpoint_url(): void {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_queried_object_id' )->justReturn( 42 );
		Functions\when( 'rest_url' )->justReturn( 'https://diariodelnorte.net/wp-json/dnorte/v1/analytics/pageview' );
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		ob_start();
		( new PageviewBeaconRenderer( new Config() ) )->render();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'post_id:42', $output );
		self::assertStringContainsString( 'https:\/\/diariodelnorte.net\/wp-json\/dnorte\/v1\/analytics\/pageview', $output );
		self::assertStringContainsString( 'navigator.sendBeacon', $output );
	}
}
