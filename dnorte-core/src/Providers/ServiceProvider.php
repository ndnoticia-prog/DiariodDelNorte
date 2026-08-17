<?php
/**
 * Contrato base de todo proveedor de servicios (módulo del plugin o del tema).
 *
 * @package DNorteCore\Providers
 */

declare(strict_types=1);

namespace DNorteCore\Providers;

use DNorteCore\Container\Container;

abstract class ServiceProvider
{
    public function __construct(protected readonly Container $container)
    {
    }

    /**
     * Registra bindings en el contenedor. No debe leer nada de WordPress todavía
     * (puede ejecutarse antes de que WordPress esté completamente cargado).
     */
    public function register(): void
    {
    }

    /**
     * Registra hooks/rutas de este módulo. Se ejecuta después de que todos los
     * providers hayan tenido oportunidad de registrar sus bindings.
     */
    public function boot(): void
    {
    }
}
