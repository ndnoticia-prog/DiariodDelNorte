<?php
/**
 * Conecta el módulo de búsqueda interna: los filtros de relevancia
 * (posts_search/posts_orderby, ver SearchQueryModifier) y el endpoint REST
 * (dnorte_core/rest_controllers).
 *
 * SearchQueryModifier se resuelve de forma diferida (dentro de modifySearch()/
 * modifyOrderby(), no aquí en boot()) a propósito: depende en cadena de
 * DatabaseManager → wpdb, una clase real de WordPress que no existe en el proceso
 * de pruebas unitarias (Brain Monkey) — resolverla en boot() rompería la prueba
 * unitaria de wiring de este provider, que solo necesita confirmar que los
 * add_filter() se registraron, no ejecutar el filtro de verdad (eso lo cubre la
 * prueba de integración de SearchQueryModifier). Mismo tipo de límite ya
 * documentado para DatabaseManager/Migrator/Installer en docs/Architecture.md.
 *
 * @package DNorteCore\Providers
 */

declare(strict_types=1);

namespace DNorteCore\Providers;

use DNorteCore\Hooks\HookManager;
use DNorteCore\RestApi\Contracts\RegistersRoutes;
use DNorteCore\Search\InternalSearchController;
use DNorteCore\Search\SearchQueryModifier;
use WP_Query;

final class SearchServiceProvider extends ServiceProvider {

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		$hooks->addFilter( 'posts_search', $this->modifySearch( ... ), 10, 2 );
		$hooks->addFilter( 'posts_orderby', $this->modifyOrderby( ... ), 10, 2 );
		$hooks->addFilter( 'dnorte_core/rest_controllers', $this->addRestControllers( ... ), 10, 1 );
	}

	public function modifySearch( string $search, WP_Query $query ): string {
		return $this->modifier()->modifySearch( $search, $query );
	}

	public function modifyOrderby( string $orderby, WP_Query $query ): string {
		return $this->modifier()->modifyOrderby( $orderby, $query );
	}

	/**
	 * @param list<class-string<RegistersRoutes>> $controllers
	 * @return list<class-string<RegistersRoutes>>
	 */
	public function addRestControllers( array $controllers ): array {
		$controllers[] = InternalSearchController::class;

		return $controllers;
	}

	private function modifier(): SearchQueryModifier {
		/** @var SearchQueryModifier $modifier */
		$modifier = $this->container->get( SearchQueryModifier::class );

		return $modifier;
	}
}
