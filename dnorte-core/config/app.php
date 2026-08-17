<?php
/**
 * Configuración base de la aplicación. Cargado automáticamente por Config::loadDirectory()
 * bajo la clave "app" (ej. Config::get('app.name')).
 *
 * @package DNorteCore
 */

declare(strict_types=1);

return [
    'name' => 'DNorte Core',
    'version' => defined('DNORTE_CORE_VERSION') ? DNORTE_CORE_VERSION : '0.1.0-alpha.1',
];
