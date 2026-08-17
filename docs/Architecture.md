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

## Qué falta por decidir/documentar aquí

Este documento cubre solo lo que ya existe (arranque, DI, hooks). A medida que se añadan
módulos (base de datos/migraciones, REST, caché, seguridad, SEO, ...) documentar en este
mismo fichero las decisiones de diseño y qué se reimplementa vs. qué se reutiliza de
WordPress core, siguiendo el mismo criterio que `handoff-nd-platform.md` §4.
