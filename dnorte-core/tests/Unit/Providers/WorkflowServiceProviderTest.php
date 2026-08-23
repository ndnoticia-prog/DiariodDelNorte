<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Providers;

use Brain\Monkey\Functions;
use DNorteCore\Container\Container;
use DNorteCore\Hooks\HookManager;
use DNorteCore\Providers\WorkflowServiceProvider;
use DNorteCore\Tests\Unit\TestCase;
use DNorteCore\Workflow\Shifts\ShiftsAdminPage;
use Mockery;

final class WorkflowServiceProviderTest extends TestCase {

	public function test_boot_wires_the_editorial_status_and_admin_pages_hooks(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'init', Mockery::type( 'callable' ), 10, 1 );
		Functions\expect( 'add_filter' )
			->once()
			->with( 'dnorte_core/admin_pages', Mockery::type( 'callable' ), 10, 1 );

		$container = new Container();
		$hooks     = new HookManager();
		$container->instance( HookManager::class, $hooks );

		( new WorkflowServiceProvider( $container ) )->boot();
		$hooks->flush();

		$this->addToAssertionCount( 1 );
	}

	public function test_add_admin_pages_appends_the_shifts_admin_page(): void {
		$container = new Container();

		$result = ( new WorkflowServiceProvider( $container ) )->addAdminPages( array() );

		self::assertSame( array( ShiftsAdminPage::class ), $result );
	}
}
