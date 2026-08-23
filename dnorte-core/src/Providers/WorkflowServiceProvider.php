<?php
/**
 * Conecta el módulo de workflow editorial: estados editoriales adicionales (init) y
 * el panel de turnos (dnorte_core/admin_pages).
 *
 * @package DNorteCore\Providers
 */

declare(strict_types=1);

namespace DNorteCore\Providers;

use DNorteCore\Admin\Contracts\RegistersAdminPages;
use DNorteCore\Hooks\HookManager;
use DNorteCore\Workflow\Shifts\ShiftsAdminPage;
use DNorteCore\Workflow\Status\EditorialStatusRegistrar;

final class WorkflowServiceProvider extends ServiceProvider {

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		$hooks->addAction( 'init', $this->registerEditorialStatuses( ... ), 10 );
		$hooks->addFilter( 'dnorte_core/admin_pages', $this->addAdminPages( ... ), 10, 1 );
	}

	public function registerEditorialStatuses(): void {
		( new EditorialStatusRegistrar() )->register();
	}

	/**
	 * @param list<class-string<RegistersAdminPages>> $registrars
	 * @return list<class-string<RegistersAdminPages>>
	 */
	public function addAdminPages( array $registrars ): array {
		$registrars[] = ShiftsAdminPage::class;

		return $registrars;
	}
}
