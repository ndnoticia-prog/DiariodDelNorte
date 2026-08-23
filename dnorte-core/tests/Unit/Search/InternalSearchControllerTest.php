<?php
/**
 * handle() no se cubre aquí: construye un WP_Query real y devuelve un
 * WP_REST_Response, ninguno existe fuera de un WordPress real — igual que
 * SystemStatusControllerTest, cerrado por la prueba de integración.
 *
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Search;

use Brain\Monkey\Functions;
use DNorteCore\Config\Config;
use DNorteCore\Routing\Router;
use DNorteCore\Search\InternalSearchController;
use DNorteCore\Tests\Unit\TestCase;
use Mockery;

final class InternalSearchControllerTest extends TestCase {

	public function test_register_routes_registers_a_public_get_endpoint_requiring_q(): void {
		Functions\expect( 'register_rest_route' )
			->once()
			->with(
				'dnorte/v1',
				'/search',
				Mockery::on(
					static function ( array $args ): bool {
						return $args['methods'] === 'GET'
							&& $args['permission_callback'] === '__return_true'
							&& is_callable( $args['callback'] )
							&& $args['args']['q']['required'] === true;
					}
				)
			);

		( new InternalSearchController( new Config() ) )->registerRoutes( new Router() );

		$this->addToAssertionCount( 1 );
	}
}
