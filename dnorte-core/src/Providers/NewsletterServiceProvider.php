<?php
/**
 * Conecta el módulo de newsletter: el endpoint REST que recibe la suscripción
 * del formulario de portada y el panel de administración de solo lectura.
 *
 * @package DNorteCore\Providers
 */

declare(strict_types=1);

namespace DNorteCore\Providers;

use DNorteCore\Admin\Contracts\RegistersAdminPages;
use DNorteCore\Hooks\HookManager;
use DNorteCore\Newsletter\NewsletterAdminPage;
use DNorteCore\Newsletter\NewsletterController;
use DNorteCore\RestApi\Contracts\RegistersRoutes;

final class NewsletterServiceProvider extends ServiceProvider {

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		$hooks->addFilter( 'dnorte_core/rest_controllers', $this->addRestControllers( ... ), 10, 1 );
		$hooks->addFilter( 'dnorte_core/admin_pages', $this->addAdminPages( ... ), 10, 1 );
	}

	/**
	 * @param list<class-string<RegistersRoutes>> $controllers
	 * @return list<class-string<RegistersRoutes>>
	 */
	public function addRestControllers( array $controllers ): array {
		$controllers[] = NewsletterController::class;

		return $controllers;
	}

	/**
	 * @param list<class-string<RegistersAdminPages>> $registrars
	 * @return list<class-string<RegistersAdminPages>>
	 */
	public function addAdminPages( array $registrars ): array {
		$registrars[] = NewsletterAdminPage::class;

		return $registrars;
	}
}
