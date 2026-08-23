<?php
/**
 * Centraliza el registro de páginas de administración de todos los módulos activos,
 * vía el filtro `dnorte_core/admin_pages` — mismo patrón que
 * `dnorte_core/providers`/`dnorte_core/rest_controllers`: un módulo se suma a la
 * lista sin que dnorte-core tenga que conocer su existencia.
 *
 * Cada AdminPage declara su propio `parentSlug` (`null` = su propia entrada de
 * nivel superior) — corregido en v0.1.0-alpha.11: la versión anterior elegía la
 * página de menor `position` de TODA la plataforma como el único nivel superior y
 * anidaba cualquier otra página nueva debajo, sin importar de qué módulo viniera
 * (ver el docblock de AdminPage::$parentSlug y "Fixed" en CHANGELOG.md).
 *
 * @package DNorteCore\Providers
 */

declare(strict_types=1);

namespace DNorteCore\Providers;

use DNorteCore\Admin\AdminPage;
use DNorteCore\Admin\Contracts\RegistersAdminPages;
use DNorteCore\Hooks\HookManager;

final class AdminMenuServiceProvider extends ServiceProvider {

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		$hooks->addAction( 'admin_menu', $this->registerMenu( ... ), 10 );
	}

	public function registerMenu(): void {
		$pages = $this->resolvePages();

		usort( $pages, static fn ( AdminPage $a, AdminPage $b ): int => $a->position <=> $b->position );

		foreach ( $pages as $page ) {
			if ( $page->parentSlug === null ) {
				add_menu_page(
					$page->pageTitle,
					$page->menuTitle,
					$page->capability,
					$page->slug,
					$page->render,
					$page->icon
				);

				continue;
			}

			add_submenu_page(
				$page->parentSlug,
				$page->pageTitle,
				$page->menuTitle,
				$page->capability,
				$page->slug,
				$page->render
			);
		}
	}

	/**
	 * @return list<AdminPage>
	 */
	private function resolvePages(): array {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		/** @var list<class-string<RegistersAdminPages>> $registrarClasses */
		$registrarClasses = $hooks->applyFilters( 'dnorte_core/admin_pages', array() );

		$pages = array();

		foreach ( $registrarClasses as $registrarClass ) {
			if ( ! class_exists( $registrarClass ) ) {
				continue;
			}

			/** @var RegistersAdminPages $registrar */
			$registrar = $this->container->get( $registrarClass );

			foreach ( $registrar->adminPages() as $page ) {
				$pages[] = $page;
			}
		}

		return $pages;
	}
}
