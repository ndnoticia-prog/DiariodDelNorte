# Roadmap

Metodología (heredada de ND Platform, ver `docs/handoff-nd-platform.md` §7): se avanza
de versión únicamente cuando la anterior compila, pasa `composer run check` (PHPCS/WPCS +
PHPStan + PHPUnit) y queda documentada en `CHANGELOG.md`. Cualquier pieza con interfaz se
verifica además contra un WordPress real en el navegador antes de cerrarse.

## v0.1.0-alpha.1 — Núcleo mínimo viable

- [x] Scaffold del repositorio (estructura, composer.json/package.json raíz, docs base).
- [x] `dnorte-core`: `Application`, `Container` (con autowiring), `Config`.
- [x] `dnorte-core`: `ServiceProvider` base y `CoreServiceProvider`.
- [x] `dnorte-core`: `HookManager` (wrapper tipado de add_action/add_filter) y
      `EventDispatcher` (bus de eventos interno).
- [x] `dnorte-core`: arranque en `after_setup_theme` (no `plugins_loaded`) desde el
      primer commit — evita el bug de orden de hooks ya encontrado en ND Platform.
- [x] `dnorte-theme`: bootstrap (`style.css`, `functions.php` con comprobación de que
      `dnorte-core` esté activo, `ThemeServiceProvider`).
- [x] `dnorte-theme`: `header.php`/`footer.php`/`index.php` mínimos, modo oscuro
      (`data-theme` + `prefers-color-scheme`, script anti-parpadeo inline) y build de
      assets con Vite/Sass.
- [x] Suite de pruebas unitarias PHPUnit (Brain Monkey): `Container`, `Config`,
      `HookManager`, `EventDispatcher` (27 pruebas) en `dnorte-core`; `ThemeServiceProvider`
      (3 pruebas) en `dnorte-theme`.
- [x] `phpcs.xml.dist`/`phpstan.neon.dist`/`phpunit.xml.dist` propios por paquete —
      `composer run check` en verde de punta a punta en ambos paquetes (0 errores PHPCS,
      0 errores PHPStan nivel máximo, 30 pruebas PHPUnit en total). Encontrados y
      corregidos en el proceso: `HookManager` le faltaba `doAction()`/`applyFilters()`
      (solo tenía la mitad del wrapper), y `Application` llamaba `apply_filters()`
      directo en vez de pasar por `HookManager` — ver "Fixed" en `CHANGELOG.md`.
- [x] Script de empaquetado (`tools/build/package.sh`) que genera
      `dnorte-core-0.1.0-alpha.1.zip`/`dnorte-theme-0.1.0-alpha.1.zip` instalables —
      solo `src/`/`config/`/`dnorte-core.php` (plugin) y `src/`/`dist/`/plantillas
      (tema), nunca `vendor/`/`tests/`/configs de desarrollo.
- [x] Verificación con el toolchain instalado: `composer install` y
      `npm install && npm run build` en verde, generan `dist/app.css`/`dist/app.js`.
- [x] Activación verificada en un WordPress real vía WP-CLI, primero con symlinks de
      desarrollo y después reemplazando ambos por los `.zip` reales generados por
      `package.sh` (instalados con `wp plugin install`/`wp theme install`, exactamente
      el flujo de "Subir plugin"/"Subir tema" de wp-admin): plugin y tema activos,
      front-end cargando (assets de Vite servidos), modo oscuro y claro funcionando sin
      parpadeo, dashboard y pantallas de administración (Plugins, Temas, Menús) sin
      errores, `debug.log` vacío en todo el recorrido. Encontrado y corregido en el
      proceso: `ThemeServiceProvider` enganchaba `registerMenus()` a un hook inexistente
      (`register_nav_menus` no es una action de WordPress) — ver "Fixed" en
      `CHANGELOG.md`. Nota: misma base de datos que las verificaciones anteriores, no
      una instalación con base de datos nueva desde cero (ver `docs/handoff-nd-platform.md`
      §5 sobre por qué ND sí exige eso para cerrar una versión — pendiente para el cierre
      real de `v0.1.0-alpha.1`).

## v0.1.0-alpha.2 — Base de datos y REST API

- [x] `dnorte-core`: `Database\DatabaseManager` (único punto de acceso a `$wpdb`,
      sentencias preparadas obligatorias — `select()`/`selectOne()`/`insert()`/
      `update()`/`delete()`/`statement()`, más `unprepared()` exclusivo para DDL de
      migraciones), `table()`/`wpTable()` para distinguir tablas propias de nativas.
- [x] `dnorte-core`: `Migrator\Migrator` (tabla propia `{prefix}dnorte_migrations`,
      migraciones idempotentes vía contrato `Migration`), `Installer\Installer`
      (corre migraciones pendientes y registra `dnorte_core_installed_version` en
      `wp_options`). Dos disparadores: `register_activation_hook()` en
      `dnorte-core.php` (primera instalación) y `CoreServiceProvider` en `init`
      (auto-reparación si la versión instalada quedó desatrasada, sin depender de
      desactivar/reactivar) — ver "Base de datos y migraciones" en
      `docs/Architecture.md`.
- [x] `dnorte-core`: `Routing\Router` (único punto de acceso a
      `register_rest_route()`), contrato `RestApi\Contracts\RegistersRoutes`,
      `Providers\RestApiServiceProvider` (filtro `dnorte_core/rest_controllers`,
      mismo patrón que `dnorte_core/providers`), y el primer endpoint real:
      `GET /wp-json/dnorte/v1/system/status` (`SystemStatusController`, público, sin
      datos sensibles).
- [x] Suite de pruebas unitarias PHPUnit (Brain Monkey) para `Router`,
      `SystemStatusController` y `RestApiServiceProvider` (5 pruebas nuevas — 32 en
      total en `dnorte-core`). `DatabaseManager`/`Migrator`/`Installer` **no** tienen
      pruebas unitarias con mocks: son `final` y dependen de `wpdb`, una clase real de
      WordPress no cargada en el proceso de pruebas — misma limitación que ND Platform
      documentó desde su alpha.1 y resolvió con pruebas de integración aparte (ver
      "Por qué..." en `docs/Architecture.md`); esa infraestructura (WordPress/MySQL
      reales) no está montada todavía en este repo — pendiente.
- [x] `composer run check` en verde (0 errores PHPCS, 0 errores PHPStan nivel máximo,
      32 pruebas PHPUnit). Encontrado en el proceso (por PHPStan, no por revisión
      manual): `wpdb::prepare()` puede devolver `null` en un error de programación —
      `DatabaseManager` no lo propagaba a `$wpdb->query()`/`get_row()`, que esperan
      `string`; corregido centralizando `prepare()` en un método privado que trata
      `null` como "sin resultado", nunca ejecuta una consulta rota.
- [x] Verificado en el WordPress real de desarrollo: `.zip` regenerado e instalado
      (`wp plugin install --force`), endpoint `GET /wp-json/dnorte/v1/system/status`
      respondiendo el JSON esperado (confirmado también con permalinks "bonitos", no
      solo `?rest_route=`), dashboard/Plugins sin errores, `debug.log` vacío en todo
      el recorrido.

## v0.1.0-alpha.3 — Infraestructura de pruebas de integración

- [x] `git sparse-checkout` de `WordPress/wordpress-develop` (`src` + `tests/phpunit`)
      en `tools/wp-tests/wordpress-develop/`, y base de datos MariaDB local
      (`dnorte_platform_test`) separada de la de ND Platform — mismo enfoque
      documentado en `handoff-nd-platform.md` §6, sin Docker/`wp-env`.
- [x] `tools/wp-tests/phpunit9/`: meta-proyecto Composer aislado con PHPUnit 9 +
      `yoast/phpunit-polyfills`, autoloader propio (`DNorteCore\` → `dnorte-core/src/`
      por ruta) — necesario porque el arnés de pruebas de `wordpress-develop`
      (`WP_UnitTestCase::expectDeprecated()`) todavía llama a un método interno de
      PHPUnit eliminado en PHPUnit 10/11 (las que usan las pruebas unitarias).
- [x] `dnorte-core.php`: guard `class_exists('DNorteCore\Application')` antes de
      requerir `vendor/autoload.php` — evita que dos copias de PHPUnit (9 del arnés de
      integración, 10 del propio paquete) coexistan en el mismo proceso. Mismo patrón
      que `nd-core.php` en ND Platform.
- [x] 14 pruebas de integración reales: `DatabaseManagerTest` (CRUD completo contra una
      tabla de fixtures propia), `MigratorTest` (aplicar/no reaplicar una migración,
      limpieza cuidadosa de la tabla compartida `dnorte_migrations`), `InstallerTest`
      (incluye confirmar que la instalación real ya corrió sola durante el bootstrap),
      y `SystemStatusControllerTest` (endpoint REST real de punta a punta vía
      `rest_get_server()->dispatch()`, cerrando el hueco que la suite unitaria dejó
      documentado para `handle()`).
- [x] `composer run check` (32 pruebas unitarias) y `composer test:integration` (14
      pruebas) en verde, ambos estables en corridas repetidas (sin estado corrupto
      entre invocaciones del proceso).
- [x] `.zip` regenerado (por el guard nuevo en `dnorte-core.php`) e instalado sobre el
      WordPress real de desarrollo: front-end, `wp-json/dnorte/v1/system/status` y
      Plugins sin errores, `debug.log` vacío.

## Próximas versiones (por decidir)

Alcance a definir según necesidad real de Diario del Norte, no por paridad con ND
Platform (ver `docs/handoff-nd-platform.md` §8). Candidatos, en orden probable de
prioridad: SEO técnico (Schema.org, OpenGraph, sitemap), multimedia (WebP/AVIF, imagen
destacada), y solo después evaluar publicidad propia, analítica propia, IA, búsqueda
interna y workflow editorial — cada uno preguntando primero si un plugin ya probado o
una función nativa de WordPress lo resuelve sin construir nada nuevo.
