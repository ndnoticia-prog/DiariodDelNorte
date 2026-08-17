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
use DNorteCore\Providers\CoreServiceProvider;
use DNorteCore\Providers\ServiceProvider;

final class Application
{
    private static ?self $instance = null;

    private readonly Container $container;

    /** @var list<ServiceProvider> */
    private array $providers = [];

    private bool $booted = false;

    private function __construct(private readonly string $pluginFile)
    {
        $this->container = new Container();
    }

    public static function instance(?string $pluginFile = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($pluginFile ?? '');
        }

        return self::$instance;
    }

    /**
     * Solo para pruebas: permite reiniciar el singleton entre casos de prueba.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->registerBaseBindings();
        $this->loadConfig();
        $this->registerProviders();
        $this->bootProviders();

        $this->container->get(HookManager::class)->flush();

        $this->booted = true;
    }

    private function registerBaseBindings(): void
    {
        $this->container->instance(Container::class, $this->container);
        $this->container->singleton(Config::class, static fn () => new Config());
        $this->container->singleton(HookManager::class, static fn () => new HookManager());
        $this->container->singleton(EventDispatcher::class, static fn () => new EventDispatcher());
    }

    private function loadConfig(): void
    {
        $configDir = dirname($this->pluginFile) . '/config';

        if (is_dir($configDir)) {
            $this->container->get(Config::class)->loadDirectory($configDir);
        }
    }

    private function registerProviders(): void
    {
        foreach ($this->resolveProviderClasses() as $providerClass) {
            if (! class_exists($providerClass)) {
                continue;
            }

            $provider = new $providerClass($this->container);
            $provider->register();
            $this->providers[] = $provider;
        }
    }

    private function bootProviders(): void
    {
        foreach ($this->providers as $provider) {
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
    private function resolveProviderClasses(): array
    {
        $defaults = [
            CoreServiceProvider::class,
        ];

        /** @var list<class-string<ServiceProvider>> $providers */
        $providers = apply_filters('dnorte_core/providers', $defaults);

        return $providers;
    }
}
