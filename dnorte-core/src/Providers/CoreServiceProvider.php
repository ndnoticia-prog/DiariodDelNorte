<?php
/**
 * Provider del núcleo: enlaza en el contenedor las piezas que ya existen
 * como instancias (Config, HookManager, EventDispatcher) creadas por Application::boot().
 *
 * @package DNorteCore\Providers
 */

declare(strict_types=1);

namespace DNorteCore\Providers;

final class CoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Punto de extensión para hooks propios del núcleo (activación diferida,
        // comprobación de versión instalada, etc.) a medida que se necesiten.
    }
}
