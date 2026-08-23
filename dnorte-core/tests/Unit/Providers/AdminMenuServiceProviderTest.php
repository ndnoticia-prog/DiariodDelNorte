<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Providers;

use Brain\Monkey\Functions;
use DNorteCore\Admin\AdminPage;
use DNorteCore\Admin\Contracts\RegistersAdminPages;
use DNorteCore\Container\Container;
use DNorteCore\Hooks\HookManager;
use DNorteCore\Providers\AdminMenuServiceProvider;
use DNorteCore\Tests\Unit\TestCase;
use Mockery;

final class AdminMenuServiceProviderTest extends TestCase {

	public function test_boot_wires_the_admin_menu_action(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_menu', Mockery::type( 'callable' ), 10, 1 );

		$container = new Container();
		$hooks     = new HookManager();
		$container->instance( HookManager::class, $hooks );

		( new AdminMenuServiceProvider( $container ) )->boot();
		$hooks->flush();

		$this->addToAssertionCount( 1 );
	}

	public function test_register_menu_does_nothing_without_any_registered_page(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\expect( 'add_menu_page' )->never();

		$container = new Container();
		$container->instance( HookManager::class, new HookManager() );

		( new AdminMenuServiceProvider( $container ) )->registerMenu();

		$this->addToAssertionCount( 1 );
	}

	public function test_register_menu_uses_the_lowest_position_page_as_the_top_level_entry(): void {
		Functions\when( 'apply_filters' )->alias(
			static function ( string $tag, array $value ) {
				if ( $tag === 'dnorte_core/admin_pages' ) {
					$value[] = FakeAdminPagesRegistrar::class;
				}

				return $value;
			}
		);

		Functions\expect( 'add_menu_page' )
			->once()
			->with( 'Turnos', 'Turnos', 'edit_posts', 'dnorte-turnos', Mockery::type( 'callable' ), 'dashicons-groups' );

		Functions\expect( 'add_submenu_page' )
			->once()
			->with( 'dnorte-turnos', 'Ajustes', 'Ajustes', 'manage_options', 'dnorte-turnos-ajustes', Mockery::type( 'callable' ) );

		$container = new Container();
		$container->instance( HookManager::class, new HookManager() );

		( new AdminMenuServiceProvider( $container ) )->registerMenu();

		$this->addToAssertionCount( 1 );
	}
}

final class FakeAdminPagesRegistrar implements RegistersAdminPages {

	public function adminPages(): array {
		return array(
			new AdminPage(
				'dnorte-turnos-ajustes',
				'Ajustes',
				'Ajustes',
				'manage_options',
				static function (): void {
				},
				20
			),
			new AdminPage(
				'dnorte-turnos',
				'Turnos',
				'Turnos',
				'edit_posts',
				static function (): void {
				},
				10,
				'dashicons-groups'
			),
		);
	}
}
