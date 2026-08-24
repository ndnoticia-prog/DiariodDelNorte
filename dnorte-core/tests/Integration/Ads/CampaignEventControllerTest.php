<?php
/**
 * Cierra el hueco documentado en la suite unitaria (CampaignEventControllerTest ahí
 * solo cubre registerRoutes(), no handle{Impression,Click}(), porque
 * WP_REST_Request/Response y el CampaignRepository que construyen con
 * `global $wpdb` no existen/no son seguros fuera de un WordPress real).
 *
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Ads;

use DNorteCore\Ads\Campaign;
use DNorteCore\Ads\CampaignRepository;
use DNorteCore\Database\DatabaseManager;
use WP_REST_Request;
use WP_UnitTestCase;

final class CampaignEventControllerTest extends WP_UnitTestCase {

	public function test_impression_endpoint_increments_the_campaigns_impression_count(): void {
		do_action( 'rest_api_init' );

		global $wpdb;
		$repository = new CampaignRepository( new DatabaseManager( $wpdb ) );
		$id         = $repository->save(
			new Campaign( 0, 'Campaña', 'Anunciante', Campaign::TYPE_HTML, true, 0, array( 'cabecera' ), array(), null, null, '<div>x</div>', '', '' )
		);

		$request = new WP_REST_Request( 'POST', '/dnorte/v1/ads/impression' );
		$request->set_param( 'campaign_id', $id );

		$response = rest_get_server()->dispatch( $request );

		self::assertSame( 204, $response->get_status() );

		$saved = $repository->find( $id );
		self::assertNotNull( $saved );
		self::assertSame( 1, $saved->impressions );
	}

	public function test_click_endpoint_increments_the_campaigns_click_count(): void {
		do_action( 'rest_api_init' );

		global $wpdb;
		$repository = new CampaignRepository( new DatabaseManager( $wpdb ) );
		$id         = $repository->save(
			new Campaign( 0, 'Campaña', 'Anunciante', Campaign::TYPE_HTML, true, 0, array( 'cabecera' ), array(), null, null, '<div>x</div>', '', '' )
		);

		$request = new WP_REST_Request( 'POST', '/dnorte/v1/ads/click' );
		$request->set_param( 'campaign_id', $id );

		$response = rest_get_server()->dispatch( $request );

		self::assertSame( 204, $response->get_status() );

		$saved = $repository->find( $id );
		self::assertNotNull( $saved );
		self::assertSame( 1, $saved->clicks );
	}
}
