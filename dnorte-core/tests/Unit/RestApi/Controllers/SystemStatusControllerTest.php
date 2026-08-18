<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\RestApi\Controllers;

use Brain\Monkey\Functions;
use DNorteCore\RestApi\Controllers\SystemStatusController;
use DNorteCore\Routing\Router;
use DNorteCore\Tests\Unit\TestCase;
use Mockery;

final class SystemStatusControllerTest extends TestCase {

	public function test_register_routes_registers_a_public_get_endpoint(): void {
		Functions\expect( 'register_rest_route' )
			->once()
			->with(
				'dnorte/v1',
				'/system/status',
				Mockery::on(
					static function ( array $args ): bool {
						return $args['methods'] === 'GET'
							&& $args['permission_callback'] === '__return_true'
							&& is_callable( $args['callback'] );
					}
				)
			);

		( new SystemStatusController() )->registerRoutes( new Router() );

		$this->addToAssertionCount( 1 );
	}
}
