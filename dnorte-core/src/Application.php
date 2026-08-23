<?php
/**
 * Punto de entrada de la aplicación: arranca el contenedor, carga la configuración
 * y resuelve/registra/bootea todos los ServiceProvider activos (del propio núcleo,
 * de módulos futuros y del tema activo).
 *
 * Lección aplicada desde el día uno (encontrada en ND Platform v0.1.0-beta.1): si este
 * arranque ocurre en `plugins_loaded`, un tema que se auto-registra en su propio
 * `functions.php` (que WordPress carga DESPUÉS de `plugins_loaded`) nunca llega a
 * tiempo — sus providers quedan fuera silenciosamente, sin error visible. Por eso
 * Application::boot() se engancha en `after_setup_theme` (ver dnorte-core.php),
 * momento en el que el `functions.php` del tema activo ya se ejecutó.
 *
 * @package DNorteCore
 */

declare(strict_types=1);

namespace DNorteCore;

use DNorteCore\Config\Config;
use DNorteCore\Container\Container;
use DNorteCore\Events\EventDispatcher;
use DNorteCore\Hooks\HookManager;
use DNorteCore\Providers\AdminMenuServiceProvider;
use DNorteCore\Providers\CoreServiceProvider;
use DNorteCore\Providers\MediaServiceProvider;
use DNorteCore\Providers\RestApiServiceProvider;
use DNorteCore\Providers\SearchServiceProvider;
use DNorteCore\Providers\SeoServiceProvider;
use DNorteCore\Providers\ServiceProvider;
use DNorteCore\Providers\WorkflowServiceProvider;
use wpdb;

final class Application {

	private static ?self $instance = null;

	private readonly Container $container;

	private readonly HookManager $hooks;

	/** @var list<ServiceProvider> */
	private array $providers = array();

	private bool $booted = false;

	private function __construct( private readonly string $pluginFile ) {
		$this->container = new Container();
		$this->hooks     = new HookManager();
	}

	public static function instance( ?string $pluginFile = null ): self {
		if ( self::$instance === null ) {
			self::$instance = new self( $pluginFile ?? '' );
		}

		return self::$instance;
	}

	/**
	 * Solo para pruebas: permite reiniciar el singleton entre casos de prueba.
	 */
	public static function reset(): void {
		self::$instance = null;
	}

	public function container(): Container {
		return $this->container;
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$config = $this->registerBaseBindings();
		$this->loadConfig( $config );
		$this->registerProviders( $config );
		$this->bootProviders();

		$this->hooks->flush();

		$this->booted = true;
	}

	private function registerBaseBindings(): Config {
		$config = new Config();

		$this->container->instance( Container::class, $this->container );
		$this->container->instance( Config::class, $config );
		$this->container->instance( HookManager::class, $this->hooks );
		$this->container->instance( EventDispatcher::class, new EventDispatcher() );

		global $wpdb;
		$this->container->instance( wpdb::class, $wpdb );

		return $config;
	}

	private function loadConfig( Config $config ): void {
		$configDir = dirname( $this->pluginFile ) . '/config';

		if ( is_dir( $configDir ) ) {
			$config->loadDirectory( $configDir );
		}
	}

	private function registerProviders( Config $config ): void {
		foreach ( $this->resolveProviderClasses( $config ) as $providerClass ) {
			if ( ! class_exists( $providerClass ) ) {
				continue;
			}

			$provider = new $providerClass( $this->container );
			$provider->register();
			$this->providers[] = $provider;
		}
	}

	private function bootProviders(): void {
		foreach ( $this->providers as $provider ) {
			$provider->boot();
		}
	}

	/**
	 * Lista de clases ServiceProvider a instanciar. El propio núcleo se añade siempre;
	 * cualquier módulo o el tema activo se suma mediante el filtro `dnorte_core/providers`
	 * (mismo patrón que `nd_core/providers` en ND Platform — permite que dnorte-theme se
	 * registre sin que dnorte-core tenga que conocer su existencia).
	 *
	 * @return list<class-string<ServiceProvider>>
	 */
	private function resolveProviderClasses( Config $config ): array {
		$defaults = array(
			CoreServiceProvider::class,
			AdminMenuServiceProvider::class,
			RestApiServiceProvider::class,
			SeoServiceProvider::class,
			MediaServiceProvider::class,
			WorkflowServiceProvider::class,
			SearchServiceProvider::class,
		);

		/** @var list<class-string<ServiceProvider>> $configured */
		$configured = $config->get( 'app.providers', array() );

		/** @var list<class-string<ServiceProvider>> $providers */
		$providers = $this->hooks->applyFilters(
			'dnorte_core/providers',
			array_values( array_unique( array( ...$defaults, ...$configured ) ) )
		);

		return $providers;
	}
}
