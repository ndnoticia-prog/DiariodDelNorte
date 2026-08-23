<?php
/**
 * Conecta el módulo de analítica propia: el beacon en wp_footer, la purga diaria
 * por WP-Cron, el endpoint REST y el panel de administración.
 *
 * PageviewPurger se resuelve de forma diferida (dentro de purgeOldPageviews(), no
 * aquí en boot()), mismo motivo documentado en SearchServiceProvider: depende en
 * cadena de wpdb, inexistente en el proceso de pruebas unitarias.
 * PageviewBeaconRenderer sí se resuelve en boot() — solo depende de Config, sin
 * ninguna cadena hacia wpdb.
 *
 * @package DNorteCore\Providers
 */

declare(strict_types=1);

namespace DNorteCore\Providers;

use DNorteCore\Analytics\AnalyticsAdminPage;
use DNorteCore\Analytics\PageviewBeaconRenderer;
use DNorteCore\Analytics\PageviewController;
use DNorteCore\Analytics\PageviewPurger;
use DNorteCore\Admin\Contracts\RegistersAdminPages;
use DNorteCore\Hooks\HookManager;
use DNorteCore\RestApi\Contracts\RegistersRoutes;

final class AnalyticsServiceProvider extends ServiceProvider {

	private const PURGE_HOOK = 'dnorte_core/analytics_purge';

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		/** @var PageviewBeaconRenderer $beacon */
		$beacon = $this->container->get( PageviewBeaconRenderer::class );

		$hooks->addAction( 'wp_footer', $beacon->render( ... ), 10 );
		$hooks->addAction( 'init', $this->schedulePurge( ... ), 20 );
		$hooks->addAction( self::PURGE_HOOK, $this->purgeOldPageviews( ... ), 10 );
		$hooks->addFilter( 'dnorte_core/rest_controllers', $this->addRestControllers( ... ), 10, 1 );
		$hooks->addFilter( 'dnorte_core/admin_pages', $this->addAdminPages( ... ), 10, 1 );
	}

	public function schedulePurge(): void {
		if ( wp_next_scheduled( self::PURGE_HOOK ) === false ) {
			wp_schedule_event( time(), 'daily', self::PURGE_HOOK );
		}
	}

	public function purgeOldPageviews(): void {
		/** @var PageviewPurger $purger */
		$purger = $this->container->get( PageviewPurger::class );

		$purger->purge();
	}

	/**
	 * @param list<class-string<RegistersRoutes>> $controllers
	 * @return list<class-string<RegistersRoutes>>
	 */
	public function addRestControllers( array $controllers ): array {
		$controllers[] = PageviewController::class;

		return $controllers;
	}

	/**
	 * @param list<class-string<RegistersAdminPages>> $registrars
	 * @return list<class-string<RegistersAdminPages>>
	 */
	public function addAdminPages( array $registrars ): array {
		$registrars[] = AnalyticsAdminPage::class;

		return $registrars;
	}
}
