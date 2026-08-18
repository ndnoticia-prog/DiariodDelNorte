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
propio. ND Platform documentó exactamente esta misma limitación desde su alpha.1.
Resuelto en `v0.1.0-alpha.2`: infraestructura de pruebas de integración con
WordPress/MySQL reales (ver "Infraestructura de pruebas de integración" más abajo).

## Infraestructura de pruebas de integración con WordPress real

`dnorte-core` tiene dos suites de PHPUnit: unitarias (Brain Monkey, `composer test`) e
integración (WordPress/MySQL reales, `composer test:integration`), siguiendo el mismo
enfoque que ND Platform documentó (ver `handoff-nd-platform.md` §6): `git
sparse-checkout` de `WordPress/wordpress-develop` para el arnés de pruebas oficial (sin
Docker/`wp-env`, no disponible en este entorno), y MariaDB local. Ver
`tools/wp-tests/README.md` para el paso a paso completo.

**PHPUnit 9 aislado**: el arnés de pruebas de `wordpress-develop`
(`WP_UnitTestCase::expectDeprecated()`) todavía llama a
`PHPUnit\Util\Test::parseTestMethodAnnotations()`, eliminado en PHPUnit 10/11 (las
que usan las pruebas unitarias). En vez de fijar todo el paquete a PHPUnit 9, las
pruebas de integración corren en un proceso completamente aislado
(`tools/wp-tests/phpunit9/`, un "meta-proyecto" Composer propio) cuyo autoloader mapea
`DNorteCore\` directamente a `dnorte-core/src/` por ruta — deliberadamente sin cargar
el `vendor/autoload.php` propio de `dnorte-core`, que arrastraría PHPUnit 10 al mismo
proceso y produciría un choque de clases.

**El guard `class_exists()` en `dnorte-core.php`**: como el autoloader del proceso de
integración ya mapea `DNorteCore\` por ruta, `dnorte-core.php` (cargado como mu-plugin
vía `tests_add_filter('muplugins_loaded', ...)`, exactamente igual que en producción)
comprueba `class_exists('DNorteCore\Application')` antes de requerir su propio
`vendor/autoload.php` — si la clase ya es resoluble (porque el autoloader del proceso
de integración ya la mapea), no vuelve a cargar el `vendor/autoload.php` del paquete, y
así nunca coexisten dos copias de PHPUnit en el mismo proceso. Mismo patrón exacto que
`nd-core.php` en ND Platform.

**Migraciones y estado compartido entre invocaciones**: `CoreServiceProvider::maybeRunUpgrade()`
(enganchado a `init`) ejecuta `Installer::install()` automáticamente en cada arranque —
exactamente igual que en un sitio real. La tabla `dnorte_migrations` ya existe para
cuando cualquier clase de prueba arranca; un test que la recree o la vacíe por completo
rompe esa invariante para el resto de la suite, porque persiste en la base de datos
compartida entre invocaciones separadas del proceso de PHPUnit. Por eso `MigratorTest`
limpia solo su propia fila de fixture en `tearDown()`, nunca la tabla completa.

**DDL y aislamiento transaccional**: `CREATE`/`DROP TABLE` producen un `COMMIT`
implícito en MySQL/MariaDB, rompiendo el aislamiento transaccional por-test de
`WP_UnitTestCase` para el resto de esa prueba. `DatabaseManagerTest` crea y elimina su
tabla de fixtures en `wpSetUpBeforeClass()`/`wpTearDownAfterClass()` (una sola vez para
toda la clase, fuera de cualquier transacción por-test), no dentro de un test
individual.

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

## SEO: qué reimplementa `dnorte-core` y qué reutiliza de WordPress core

- **`SeoContext`/`SeoContextResolver`** (`Seo\Context\`): única fuente de verdad por
  página (singular, home, archivo, búsqueda, 404). Meta tags, OpenGraph, Twitter Cards
  y Schema.org consumen siempre la misma instancia — nunca resuelven el contexto por su
  cuenta, para que no puedan divergir entre sí.
- **Sitemap general**: no se reimplementa. WordPress core expone `wp-sitemap.xml` (y
  sus sub-sitemaps) desde la 5.5; `Seo\Robots\RobotsTxtBuilder` solo le añade la
  directiva `Sitemap:` a `robots.txt`. El sitemap específico de Google News (que sí
  requeriría reimplementación, por el namespace `news:` y la ventana de tiempo corta)
  queda fuera de alcance por ahora — ver "Próximas versiones" en `ROADMAP.md`.
- **JSON-LD como un único `@graph`** (`Seo\Schema\SchemaOutput`), no un `<script>` por
  tipo, codificado con `JSON_HEX_TAG | JSON_HEX_AMP`: sin esos flags, un título de
  artículo que contuviera literalmente `</script>` cerraría el bloque e inyectaría
  HTML/JS arbitrario en la página. Mismo criterio que ND Platform.
- **Breadcrumbs y el título de archivo no son la misma cadena**:
  `Seo\Breadcrumbs\BreadcrumbBuilder` usa el nombre del término directamente
  (`WP_Term::$name`) en vez de `get_the_archive_title()` para las migas de pan de
  categoría/etiqueta — WordPress core antepone "Category: "/"Tag: " al título de
  archivo (correcto para el `<title>` SEO, que sí usa `get_the_archive_title()` vía
  `SeoContextResolver`, pero no para una miga de pan). Encontrado por la prueba de
  integración de `BreadcrumbBuilder`, no por revisión manual.
- **Por qué `SeoContextResolver`/`BreadcrumbBuilder`/`ArticleSchema` sí tienen pruebas
  de integración (y no solo unitarias)**: dependen de `WP_Post`/`WP_Term`/consultas
  reales (`get_queried_object()`, `get_the_category()`, ...) — misma limitación que
  `DatabaseManager`/`Migrator`/`Installer` (ver más arriba), cubierta ahora que la
  infraestructura de integración ya existe desde `v0.1.0-alpha.3`. Los constructores
  puros (`RobotsMetaBuilder`, `OpenGraphBuilder`, `TwitterCardBuilder`,
  `MetaTagsRenderer`, `OrganizationSchema`, `WebSiteSchema`, `BreadcrumbListSchema`,
  `SchemaOutput`, `RobotsTxtBuilder`) sí se cubren con pruebas unitarias (Brain Monkey),
  porque solo reciben datos ya resueltos (un `SeoContext` o una lista de ítems), nunca
  tocan `WP_Post` directamente.

## Qué falta por decidir/documentar aquí

A medida que se añadan más módulos (caché, seguridad, multimedia, ...) documentar en
este mismo fichero las decisiones de diseño y qué se reimplementa vs. qué se reutiliza
de WordPress core, siguiendo el mismo criterio que `handoff-nd-platform.md` §4.
