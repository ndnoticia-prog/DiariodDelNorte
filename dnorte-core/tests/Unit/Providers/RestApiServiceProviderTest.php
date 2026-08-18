<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Providers;

use Brain\Monkey\Functions;
use DNorteCore\Container\Container;
use DNorteCore\Hooks\HookManager;
use DNorteCore\Providers\RestApiServiceProvider;
use DNorteCore\Tests\Unit\TestCase;
use Mockery;

final class RestApiServiceProviderTest extends TestCase {

	public function test_boot_wires_rest_api_init(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'rest_api_init', Mockery::type( 'callable' ), 10, 1 );

		$container = new Container();
		$hooks     = new HookManager();
		$container->instance( HookManager::class, $hooks );

		( new RestApiServiceProvider( $container ) )->boot();
		$hooks->flush();

		$this->addToAssertionCount( 1 );
	}

	public function test_register_routes_registers_the_default_system_status_controller(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		Functions\expect( 'register_rest_route' )
			->once()
			->with( 'dnorte/v1', '/system/status', Mockery::type( 'array' ) );

		$container = new Container();
		$container->instance( HookManager::class, new HookManager() );

		( new RestApiServiceProvider( $container ) )->registerRoutes();

		$this->addToAssertionCount( 1 );
	}

	public function test_register_routes_lets_the_filter_add_extra_controllers(): void {
		Functions\when( 'apply_filters' )->alias(
			static function ( string $tag, array $value ) {
				if ( $tag === 'dnorte_core/rest_controllers' ) {
					$value[] = ExtraStatusController::class;
				}

				return $value;
			}
		);

		Functions\expect( 'register_rest_route' )->twice();

		$container = new Container();
		$container->instance( HookManager::class, new HookManager() );

		( new RestApiServiceProvider( $container ) )->registerRoutes();

		$this->addToAssertionCount( 1 );
	}
}

final class ExtraStatusController implements \DNorteCore\RestApi\Contracts\RegistersRoutes {

	public function registerRoutes( \DNorteCore\Routing\Router $router ): void {
		$router->register( 'dnorte/v1', '/extra', array( 'methods' => 'GET' ) );
	}
}
