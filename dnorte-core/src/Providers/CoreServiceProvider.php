<?php
/**
 * Provider del núcleo: enlaza en el contenedor las piezas que ya existen
 * como instancias (Config, HookManager, EventDispatcher) creadas por Application::boot(),
 * y se autorregenera en cada carga si la versión instalada quedó desactualizada
 * (mismo propósito que `register_activation_hook()` en dnorte-core.php, pero cubre
 * también el caso de actualizar el plugin sin desactivar/reactivar).
 *
 * @package DNorteCore\Providers
 */

declare(strict_types=1);

namespace DNorteCore\Providers;

use DNorteCore\Hooks\HookManager;
use DNorteCore\Installer\Installer;

final class CoreServiceProvider extends ServiceProvider {

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		$hooks->addAction( 'init', $this->maybeRunUpgrade( ... ), 5 );
	}

	public function maybeRunUpgrade(): void {
		if ( ! defined( 'DNORTE_CORE_VERSION' ) ) {
			return;
		}

		/** @var Installer $installer */
		$installer = $this->container->get( Installer::class );

		if ( ! $installer->needsInstall( DNORTE_CORE_VERSION ) ) {
			return;
		}

		$installer->install( array(), DNORTE_CORE_VERSION );
	}
}
