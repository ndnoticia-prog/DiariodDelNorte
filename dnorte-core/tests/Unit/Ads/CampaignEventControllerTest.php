<?php
/**
 * handle{Impression,Click}() no se cubren aquí: arman un CampaignRepository real
 * (con `global $wpdb`) y devuelven un WP_REST_Response, ninguno existe fuera de
 * un WordPress real — cerrado por la prueba de integración.
 *
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Ads;

use Brain\Monkey\Functions;
use DNorteCore\Ads\CampaignEventController;
use DNorteCore\Routing\Router;
use DNorteCore\Tests\Unit\TestCase;
use Mockery;

final class CampaignEventControllerTest extends TestCase {

	public function test_register_routes_registers_the_impression_and_click_endpoints(): void {
		Functions\expect( 'register_rest_route' )
			->once()
			->with(
				'dnorte/v1',
				'/ads/impression',
				Mockery::on(
					static function ( array $args ): bool {
						return $args['methods'] === 'POST'
							&& $args['permission_callback'] === '__return_true'
							&& is_callable( $args['callback'] )
							&& $args['args']['campaign_id']['required'] === true;
					}
				)
			);

		Functions\expect( 'register_rest_route' )
			->once()
			->with(
				'dnorte/v1',
				'/ads/click',
				Mockery::on(
					static function ( array $args ): bool {
						return $args['methods'] === 'POST'
							&& $args['permission_callback'] === '__return_true'
							&& is_callable( $args['callback'] )
							&& $args['args']['campaign_id']['required'] === true;
					}
				)
			);

		( new CampaignEventController() )->registerRoutes( new Router() );

		$this->addToAssertionCount( 1 );
	}
}
