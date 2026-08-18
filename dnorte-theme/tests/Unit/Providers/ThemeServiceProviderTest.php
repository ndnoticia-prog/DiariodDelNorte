<?php
/**
 * @package DNorteTheme\Tests
 */

declare(strict_types=1);

namespace DNorteTheme\Tests\Unit\Providers;

use Brain\Monkey\Functions;
use DNorteCore\Container\Container;
use DNorteCore\Hooks\HookManager;
use DNorteTheme\Providers\ThemeServiceProvider;
use DNorteTheme\Tests\Unit\TestCase;

final class ThemeServiceProviderTest extends TestCase {

	public function test_boot_wires_theme_supports_menus_and_asset_enqueue_to_the_correct_hooks(): void {
		$wiredHooks = array();

		Functions\expect( 'add_action' )
			->times( 3 )
			->andReturnUsing(
				function ( string $hook ) use ( &$wiredHooks ): bool {
					$wiredHooks[] = $hook;

					return true;
				}
			);

		$container = new Container();
		$hooks     = new HookManager();
		$container->instance( HookManager::class, $hooks );

		$provider = new ThemeServiceProvider( $container );
		$provider->boot();

		// register_nav_menus() se llama en after_setup_theme, no en un hook propio (ver
		// el bug real documentado en el docblock de ThemeServiceProvider::boot()); por
		// eso 'after_setup_theme' aparece dos veces y no 'register_nav_menus'.
		$hooks->flush();

		self::assertSame( array( 'after_setup_theme', 'after_setup_theme', 'wp_enqueue_scripts' ), $wiredHooks );
	}

	public function test_register_theme_supports_enables_the_expected_features(): void {
		$enabled = array();

		Functions\expect( 'add_theme_support' )
			->andReturnUsing(
				function ( string $feature ) use ( &$enabled ): bool {
					$enabled[] = $feature;

					return true;
				}
			);

		( new ThemeServiceProvider( new Container() ) )->registerThemeSupports();

		self::assertSame(
			array( 'title-tag', 'post-thumbnails', 'html5', 'automatic-feed-links', 'responsive-embeds' ),
			$enabled
		);
	}

	public function test_register_menus_registers_primary_and_footer_locations(): void {
		$registered = null;

		Functions\expect( 'register_nav_menus' )
			->once()
			->andReturnUsing(
				function ( array $menus ) use ( &$registered ): void {
					$registered = $menus;
				}
			);

		( new ThemeServiceProvider( new Container() ) )->registerMenus();

		self::assertSame( array( 'primary', 'footer' ), array_keys( (array) $registered ) );
	}
}
