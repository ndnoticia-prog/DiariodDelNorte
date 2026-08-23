<?php
/**
 * handle() no se cubre aquí: arma un PageviewRepository real (con
 * `global $wpdb`) y devuelve un WP_REST_Response, ninguno existe fuera de un
 * WordPress real — cerrado por la prueba de integración.
 *
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Analytics;

use Brain\Monkey\Functions;
use DNorteCore\Analytics\PageviewController;
use DNorteCore\Routing\Router;
use DNorteCore\Tests\Unit\TestCase;
use Mockery;

final class PageviewControllerTest extends TestCase {

	public function test_register_routes_registers_a_public_post_endpoint_requiring_post_id(): void {
		Functions\expect( 'register_rest_route' )
			->once()
			->with(
				'dnorte/v1',
				'/analytics/pageview',
				Mockery::on(
					static function ( array $args ): bool {
						return $args['methods'] === 'POST'
							&& $args['permission_callback'] === '__return_true'
							&& is_callable( $args['callback'] )
							&& $args['args']['post_id']['required'] === true;
					}
				)
			);

		( new PageviewController() )->registerRoutes( new Router() );

		$this->addToAssertionCount( 1 );
	}
}
