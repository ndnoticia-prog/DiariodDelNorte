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

	public function test_register_menu_nests_a_page_under_its_declared_parent_slug(): void {
		Functions\when( 'apply_filters' )->alias(
			static function ( string $tag, array $value ) {
				if ( $tag === 'dnorte_core/admin_pages' ) {
					$value[] = FakeWorkflowAdminPagesRegistrar::class;
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

	/**
	 * Prueba de regresión del hallazgo de v0.1.0-alpha.11 (ver el docblock de
	 * AdminPage::$parentSlug): dos páginas de módulos SIN ninguna relación entre sí
	 * (sin $parentSlug) deben volverse dos entradas de nivel superior
	 * independientes — nunca una anidada bajo la otra solo por tener la posición
	 * más alta.
	 */
	public function test_register_menu_gives_two_unrelated_modules_their_own_top_level_entry(): void {
		Functions\when( 'apply_filters' )->alias(
			static function ( string $tag, array $value ) {
				if ( $tag === 'dnorte_core/admin_pages' ) {
					$value[] = FakeWorkflowAdminPagesRegistrar::class;
					$value[] = FakeAnalyticsAdminPagesRegistrar::class;
				}

				return $value;
			}
		);

		Functions\expect( 'add_menu_page' )
			->once()
			->with( 'Turnos', 'Turnos', 'edit_posts', 'dnorte-turnos', Mockery::type( 'callable' ), 'dashicons-groups' );

		Functions\expect( 'add_menu_page' )
			->once()
			->with( 'Analítica', 'Analítica', 'edit_others_posts', 'dnorte-analitica', Mockery::type( 'callable' ), 'dashicons-chart-bar' );

		Functions\expect( 'add_submenu_page' )
			->once()
			->with( 'dnorte-turnos', 'Ajustes', 'Ajustes', 'manage_options', 'dnorte-turnos-ajustes', Mockery::type( 'callable' ) );

		$container = new Container();
		$container->instance( HookManager::class, new HookManager() );

		( new AdminMenuServiceProvider( $container ) )->registerMenu();

		$this->addToAssertionCount( 1 );
	}
}

final class FakeWorkflowAdminPagesRegistrar implements RegistersAdminPages {

	public function adminPages(): array {
		return array(
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
			new AdminPage(
				'dnorte-turnos-ajustes',
				'Ajustes',
				'Ajustes',
				'manage_options',
				static function (): void {
				},
				20,
				'dashicons-admin-generic',
				'dnorte-turnos'
			),
		);
	}
}

final class FakeAnalyticsAdminPagesRegistrar implements RegistersAdminPages {

	public function adminPages(): array {
		return array(
			new AdminPage(
				'dnorte-analitica',
				'Analítica',
				'Analítica',
				'edit_others_posts',
				static function (): void {
				},
				10,
				'dashicons-chart-bar'
			),
		);
	}
}
