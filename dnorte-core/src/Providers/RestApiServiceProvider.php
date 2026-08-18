<?php
/**
 * Centraliza el registro de rutas REST de todos los módulos activos, vía el filtro
 * `dnorte_core/rest_controllers` — mismo patrón que `dnorte_core/providers`: un
 * módulo se suma a la lista sin que dnorte-core tenga que conocer su existencia.
 *
 * @package DNorteCore\Providers
 */

declare(strict_types=1);

namespace DNorteCore\Providers;

use DNorteCore\Hooks\HookManager;
use DNorteCore\RestApi\Contracts\RegistersRoutes;
use DNorteCore\RestApi\Controllers\SystemStatusController;
use DNorteCore\Routing\Router;

final class RestApiServiceProvider extends ServiceProvider {

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		$hooks->addAction( 'rest_api_init', $this->registerRoutes( ... ), 10 );
	}

	public function registerRoutes(): void {
		$router = new Router();

		foreach ( $this->resolveControllerClasses() as $controllerClass ) {
			if ( ! class_exists( $controllerClass ) ) {
				continue;
			}

			/** @var RegistersRoutes $controller */
			$controller = $this->container->get( $controllerClass );
			$controller->registerRoutes( $router );
		}
	}

	/**
	 * @return list<class-string<RegistersRoutes>>
	 */
	private function resolveControllerClasses(): array {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		$defaults = array(
			SystemStatusController::class,
		);

		/** @var list<class-string<RegistersRoutes>> $controllers */
		$controllers = $hooks->applyFilters( 'dnorte_core/rest_controllers', $defaults );

		return $controllers;
	}
}
