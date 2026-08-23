<?php
/**
 * Centraliza el registro de páginas de administración de todos los módulos activos,
 * vía el filtro `dnorte_core/admin_pages` — mismo patrón que
 * `dnorte_core/providers`/`dnorte_core/rest_controllers`: un módulo se suma a la
 * lista sin que dnorte-core tenga que conocer su existencia.
 *
 * La página con la `position` más baja fija el slug del menú de nivel superior
 * (`add_menu_page()`) en vez de crear una página "índice" separada — mismo patrón
 * que WooCommerce/Yoast SEO, evita un submenú duplicado/huérfano.
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

		if ( $pages === array() ) {
			return;
		}

		usort( $pages, static fn ( AdminPage $a, AdminPage $b ): int => $a->position <=> $b->position );

		$topLevel = $pages[0];

		add_menu_page(
			$topLevel->pageTitle,
			$topLevel->menuTitle,
			$topLevel->capability,
			$topLevel->slug,
			$topLevel->render,
			$topLevel->icon
		);

		foreach ( $pages as $index => $page ) {
			if ( $index === 0 ) {
				continue;
			}

			add_submenu_page(
				$topLevel->slug,
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
