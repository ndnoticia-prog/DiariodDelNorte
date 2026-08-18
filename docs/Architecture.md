# Arquitectura de DNorte Platform

> Motor y tema propios de Diario del Norte. Repositorio independiente de ND Platform;
> no reutiliza ni hace fork de `nd-core`. Ver [`handoff-nd-platform.md`](handoff-nd-platform.md)
> para el razonamiento detrás de cada decisión heredada.

## Principios

1. **`dnorte-core` es el único punto de acceso a WordPress.** Ningún otro módulo llama
   directamente a `add_action`, `add_filter`, `$wpdb` o funciones nativas de WP fuera de
   `DNorteCore\Hooks\HookManager` / `DNorteCore\Database\...` (cuando exista).
2. **`dnorte-theme` no contiene lógica de negocio.** Solo composición visual.
3. **Inyección de dependencias explícita**, resuelta por `DNorteCore\Container\Container`
   (autowiring por reflexión; sin `new` fuera de un `ServiceProvider`/factory).
4. **No reimplementar lo que WordPress core ya resuelve bien.** Antes de construir algo,
   preguntar si un filtro/hook nativo ya lo cubre — ver la tabla en
   `handoff-nd-platform.md` §4.
5. **Alcance v1 mínimo**: núcleo + tema + lo estrictamente necesario para operar el sitio.
   Módulos adicionales (SEO técnico, multimedia, ads, analítica propia, IA, búsqueda,
   workflow editorial) se evalúan uno a uno según necesidad real, no por paridad con ND.

## Ciclo de vida de la aplicación

```
WordPress carga dnorte-core.php (bootstrap del plugin)
  → add_action('after_setup_theme', ..., 5)   ← NO plugins_loaded, ver nota abajo
      → Application::boot()
          → Container: bindings base (Config, HookManager, EventDispatcher)
          → Carga config/*.php → Config
          → Resuelve ServiceProviders (núcleo + los que se registren vía filtro)
              → register(): bindings del módulo
              → boot(): hooks/rutas del módulo
          → HookManager::flush(): listeners → add_action/add_filter reales
```

**Por qué `after_setup_theme` y no `plugins_loaded`**: el tema activo se registra en el
filtro `dnorte_core/providers` desde su propio `functions.php`, que WordPress carga
*después* de `plugins_loaded`. Arrancar en `plugins_loaded` perdería silenciosamente los
providers del tema — bug real ya encontrado y documentado en ND Platform (ver
`handoff-nd-platform.md` §5.1). Aplicado aquí desde el primer commit, no como corrección
posterior.

## Namespaces

| Módulo | Namespace raíz |
|---|---|
| dnorte-core (plugin) | `DNorteCore\` |
| dnorte-theme (tema) | `DNorteTheme\` |

## Extender el registro de providers desde otro módulo o el tema

```php
add_filter('dnorte_core/providers', function (array $providers): array {
    $providers[] = MiModulo\Providers\MiModuloServiceProvider::class;

    return $providers;
});
```

Mismo patrón que ND Platform usa para `nd_core/providers`, `nd_core/rest_controllers` y
`nd_core/admin_pages`: un filtro público + una lista de clases, sin que `dnorte-core`
tenga que conocer la existencia de cada módulo que se añada más adelante.

## Base de datos y migraciones

`DNorteCore\Database\DatabaseManager` envuelve `$wpdb` con sentencias preparadas
obligatorias (nunca interpolación directa) — único punto de acceso a `$wpdb` de toda la
plataforma. `DNorteCore\Migrator\Migrator` versiona el esquema propio en una tabla
`{prefix}dnorte_migrations`, ejecutando cada `Migration` (`name()`/`up()`/`down()`) una
sola vez. `DNorteCore\Installer\Installer` orquesta la corrida y registra la versión
instalada en `wp_options` (`dnorte_core_installed_version`).

Dos disparadores, no uno: `register_activation_hook()` (en `dnorte-core.php`, tiene que
llamarse en carga del archivo principal — no se puede diferir a `Application::boot()`,
que corre en `after_setup_theme`) cubre la primera instalación; `CoreServiceProvider`
además revisa en cada `init` si la versión instalada quedó desactualizada y vuelve a
correr el instalador — cubre actualizar el plugin sin desactivar/reactivar. Mismo patrón
que `CoreServiceProvider::maybeRunUpgrade()` en ND Platform.

**Por qué `DatabaseManager`/`Migrator`/`Installer` no tienen pruebas unitarias con
mocks**: `DatabaseManager` y `Migrator` son `final` (como toda la plataforma) y
dependen de `wpdb`, una clase real de WordPress no cargada en el proceso de pruebas
unitarias — ni PHPUnit ni Mockery pueden generar un doble de una clase `final`, y
`wpdb` tampoco existe fuera de un WordPress real para poder sustituirlo por un doble
propio. ND Platform documentó exactamente esta misma limitación desde su alpha.1 y la
resolvió con una suite de pruebas de integración aparte, contra un WordPress/MySQL
reales (ver `handoff-nd-platform.md` §6) — infraestructura que este proyecto todavía no
tiene montada. Mientras tanto, esta capa se verifica manualmente contra el WordPress de
desarrollo real (ver `CHANGELOG.md`).

## REST API

Mismo patrón que el registro de providers: un filtro público
(`dnorte_core/rest_controllers`) + un contrato (`RegistersRoutes`) + un value object
mínimo (`Router`, que envuelve `register_rest_route()` — único punto de acceso). Todo
controlador se resuelve vía el `Container` y se registra en `rest_api_init`, centralizado
por `RestApiServiceProvider`. Cualquier módulo se suma sin que `dnorte-core` tenga que
conocer su existencia:

```php
add_filter('dnorte_core/rest_controllers', function (array $controllers): array {
    $controllers[] = MiModulo\RestApi\Controllers\MiControlador::class;

    return $controllers;
});
```

`SystemStatusController` (`GET /wp-json/dnorte/v1/system/status`) es el primer y único
endpoint por ahora — estado público sin datos sensibles (versión del plugin/tema/WP),
mismo propósito que `GET /wp-json/nd/v1/system/status` en ND Platform.

## Qué falta por decidir/documentar aquí

A medida que se añadan más módulos (caché, seguridad, SEO, ...) documentar en este mismo
fichero las decisiones de diseño y qué se reimplementa vs. qué se reutiliza de WordPress
core, siguiendo el mismo criterio que `handoff-nd-platform.md` §4.
