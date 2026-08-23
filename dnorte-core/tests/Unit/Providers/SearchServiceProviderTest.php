<?php
/**
 * modifySearch()/modifyOrderby() en sí (la resolución de SearchQueryModifier vía el
 * contenedor y su comportamiento real) no se cubren aquí a propósito — ver el
 * docblock de SearchServiceProvider: dependen en cadena de wpdb, que no existe en
 * este proceso de pruebas. Los cubre SearchQueryModifierTest de integración.
 *
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Providers;

use Brain\Monkey\Functions;
use DNorteCore\Container\Container;
use DNorteCore\Hooks\HookManager;
use DNorteCore\Providers\SearchServiceProvider;
use DNorteCore\Search\InternalSearchController;
use DNorteCore\Tests\Unit\TestCase;
use Mockery;

final class SearchServiceProviderTest extends TestCase {

	public function test_boot_wires_the_search_relevance_and_rest_controller_hooks(): void {
		Functions\expect( 'add_filter' )
			->once()
			->with( 'posts_search', Mockery::type( 'callable' ), 10, 2 );
		Functions\expect( 'add_filter' )
			->once()
			->with( 'posts_orderby', Mockery::type( 'callable' ), 10, 2 );
		Functions\expect( 'add_filter' )
			->once()
			->with( 'dnorte_core/rest_controllers', Mockery::type( 'callable' ), 10, 1 );

		$container = new Container();
		$hooks     = new HookManager();
		$container->instance( HookManager::class, $hooks );

		( new SearchServiceProvider( $container ) )->boot();
		$hooks->flush();

		$this->addToAssertionCount( 1 );
	}

	public function test_add_rest_controllers_appends_the_internal_search_controller(): void {
		$container = new Container();

		$result = ( new SearchServiceProvider( $container ) )->addRestControllers( array() );

		self::assertSame( array( InternalSearchController::class ), $result );
	}
}
