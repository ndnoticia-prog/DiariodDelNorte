<?php
/**
 * purgeOldPageviews() en sí (la resolución de PageviewPurger vía el contenedor y su
 * comportamiento real) no se cubre aquí a propósito — ver el docblock de
 * AnalyticsServiceProvider: depende en cadena de wpdb, inexistente en este proceso
 * de pruebas. Lo cubre PageviewPurgerTest de integración.
 *
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Providers;

use Brain\Monkey\Functions;
use DNorteCore\Config\Config;
use DNorteCore\Container\Container;
use DNorteCore\Hooks\HookManager;
use DNorteCore\Providers\AnalyticsServiceProvider;
use DNorteCore\Analytics\AnalyticsAdminPage;
use DNorteCore\Analytics\PageviewController;
use DNorteCore\Tests\Unit\TestCase;
use Mockery;

final class AnalyticsServiceProviderTest extends TestCase {

	public function test_boot_wires_the_beacon_purge_and_registration_hooks(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'wp_footer', Mockery::type( 'callable' ), 10, 1 );
		Functions\expect( 'add_action' )
			->once()
			->with( 'init', Mockery::type( 'callable' ), 20, 1 );
		Functions\expect( 'add_action' )
			->once()
			->with( 'dnorte_core/analytics_purge', Mockery::type( 'callable' ), 10, 1 );
		Functions\expect( 'add_filter' )
			->once()
			->with( 'dnorte_core/rest_controllers', Mockery::type( 'callable' ), 10, 1 );
		Functions\expect( 'add_filter' )
			->once()
			->with( 'dnorte_core/admin_pages', Mockery::type( 'callable' ), 10, 1 );

		$container = new Container();
		$hooks     = new HookManager();
		$container->instance( HookManager::class, $hooks );
		$container->instance( Config::class, new Config() );

		( new AnalyticsServiceProvider( $container ) )->boot();
		$hooks->flush();

		$this->addToAssertionCount( 1 );
	}

	public function test_schedule_purge_schedules_a_daily_event_only_if_not_already_scheduled(): void {
		Functions\when( 'wp_next_scheduled' )->justReturn( false );
		Functions\expect( 'wp_schedule_event' )
			->once()
			->with( Mockery::type( 'integer' ), 'daily', 'dnorte_core/analytics_purge' );

		$container = new Container();

		( new AnalyticsServiceProvider( $container ) )->schedulePurge();

		$this->addToAssertionCount( 1 );
	}

	public function test_schedule_purge_does_nothing_if_already_scheduled(): void {
		Functions\when( 'wp_next_scheduled' )->justReturn( time() );
		Functions\expect( 'wp_schedule_event' )->never();

		$container = new Container();

		( new AnalyticsServiceProvider( $container ) )->schedulePurge();

		$this->addToAssertionCount( 1 );
	}

	public function test_add_rest_controllers_appends_the_pageview_controller(): void {
		$container = new Container();

		$result = ( new AnalyticsServiceProvider( $container ) )->addRestControllers( array() );

		self::assertSame( array( PageviewController::class ), $result );
	}

	public function test_add_admin_pages_appends_the_analytics_admin_page(): void {
		$container = new Container();

		$result = ( new AnalyticsServiceProvider( $container ) )->addAdminPages( array() );

		self::assertSame( array( AnalyticsAdminPage::class ), $result );
	}
}
