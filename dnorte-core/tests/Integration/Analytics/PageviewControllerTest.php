<?php
/**
 * Cierra el hueco documentado en la suite unitaria (PageviewControllerTest ahí solo
 * cubre registerRoutes(), no handle(), porque WP_REST_Request/Response y la
 * PageviewRepository que construye con `global $wpdb` no existen/no son seguras
 * fuera de un WordPress real).
 *
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Analytics;

use DateTimeImmutable;
use DateTimeZone;
use DNorteCore\Analytics\Pageviews\PageviewRepository;
use DNorteCore\Database\DatabaseManager;
use WP_REST_Request;
use WP_UnitTestCase;

final class PageviewControllerTest extends WP_UnitTestCase {

	public function test_it_records_a_pageview_for_a_published_post(): void {
		do_action( 'rest_api_init' );

		$postId = self::factory()->post->create( array( 'post_status' => 'publish' ) );

		$request = new WP_REST_Request( 'POST', '/dnorte/v1/analytics/pageview' );
		$request->set_param( 'post_id', $postId );
		$request->set_param( 'referrer', 'https://www.google.com/search?q=algo' );

		$response = rest_get_server()->dispatch( $request );

		self::assertSame( 204, $response->get_status() );

		global $wpdb;
		$repository = new PageviewRepository( new DatabaseManager( $wpdb ) );
		$total      = $repository->totalSince( ( new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )->modify( '-1 hour' ) );

		self::assertSame( 1, $total );
	}

	public function test_it_stores_only_the_referrer_host_never_the_full_url(): void {
		do_action( 'rest_api_init' );

		$postId = self::factory()->post->create( array( 'post_status' => 'publish' ) );

		$request = new WP_REST_Request( 'POST', '/dnorte/v1/analytics/pageview' );
		$request->set_param( 'post_id', $postId );
		$request->set_param( 'referrer', 'https://www.google.com/search?q=un+termino+privado' );

		rest_get_server()->dispatch( $request );

		global $wpdb;
		$row = $wpdb->get_row(
			"SELECT referrer_host FROM {$wpdb->prefix}dnorte_pageviews ORDER BY id DESC LIMIT 1",
			ARRAY_A
		);

		self::assertSame( 'www.google.com', $row['referrer_host'] );
	}

	public function test_it_ignores_pageviews_for_a_post_that_is_not_published(): void {
		do_action( 'rest_api_init' );

		$postId = self::factory()->post->create( array( 'post_status' => 'draft' ) );

		$request = new WP_REST_Request( 'POST', '/dnorte/v1/analytics/pageview' );
		$request->set_param( 'post_id', $postId );

		$response = rest_get_server()->dispatch( $request );

		self::assertSame( 204, $response->get_status() );

		global $wpdb;
		$repository = new PageviewRepository( new DatabaseManager( $wpdb ) );
		$total      = $repository->totalSince( ( new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )->modify( '-1 hour' ) );

		self::assertSame( 0, $total );
	}
}
