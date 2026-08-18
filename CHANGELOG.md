# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

## [Unreleased] — v0.1.0-alpha.2

### Added

- `dnorte-core`: `Database\DatabaseManager` (único punto de acceso a `$wpdb`,
  sentencias preparadas obligatorias), `Migrator\Migrator` (tabla propia
  `dnorte_migrations`, migraciones idempotentes), `Installer\Installer` (corre
  migraciones pendientes, registra `dnorte_core_installed_version`).
- `dnorte-core`: `register_activation_hook()` en `dnorte-core.php` + auto-reparación en
  `init` vía `CoreServiceProvider::maybeRunUpgrade()` (cubre actualizar el plugin sin
  desactivar/reactivar).
- `dnorte-core`: `Routing\Router`, contrato `RestApi\Contracts\RegistersRoutes`,
  `Providers\RestApiServiceProvider` (filtro `dnorte_core/rest_controllers`) y el
  primer endpoint real, `GET /wp-json/dnorte/v1/system/status`
  (`SystemStatusController`).
- 5 pruebas unitarias PHPUnit (Brain Monkey) nuevas: `Router`, `SystemStatusController`,
  `RestApiServiceProvider` (32 en total en `dnorte-core`).

### Fixed

- `dnorte-core`: `wpdb::prepare()` puede devolver `null` cuando detecta un error de
  programación (marcadores de posición que no coinciden con los parámetros dados);
  `DatabaseManager` no lo contemplaba y podía propagar ese `null` a `$wpdb->query()`/
  `get_row()`, que esperan `string`. Encontrado por PHPStan (nivel máximo), no por
  revisión manual. Corregido centralizando la preparación en un método privado que
  trata `null` como "sin resultado" — nunca ejecuta una consulta rota.

### Verified

- `composer run check` en verde (0 errores PHPCS, 0 errores PHPStan nivel máximo, 32
  pruebas PHPUnit).
- `.zip` de `dnorte-core` regenerado con los módulos nuevos e instalado sobre el
  WordPress real de desarrollo (`wp plugin install --force`): `GET
  /wp-json/dnorte/v1/system/status` responde el JSON esperado (verificado con
  permalinks "bonitos" y con `?rest_route=`), dashboard/Plugins sin errores, `debug.log`
  vacío en todo el recorrido.

### Nota de alcance

`DatabaseManager`/`Migrator`/`Installer` no tienen pruebas unitarias con mocks: son
`final` (PHPUnit/Mockery no pueden generar un doble) y dependen de `wpdb`, una clase
real de WordPress no cargada en el proceso de pruebas unitarias. Misma limitación que ND
Platform documentó desde su propio alpha.1 y resolvió con una suite de pruebas de
integración aparte, contra un WordPress/MySQL reales — infraestructura pendiente de
montar en este repo (ver `docs/handoff-nd-platform.md` §6 y "Próximas versiones" en
`ROADMAP.md`).

## [Unreleased] — v0.1.0-alpha.1

### Added

- `tools/build/package.sh`: genera `dnorte-core-<version>.zip`/`dnorte-theme-<version>.zip`
  instalables — solo `src/`/`config/`/`dnorte-core.php` en el plugin y
  `src/`/`dist/`/plantillas en el tema, nunca `vendor/`/`tests/`/configs de desarrollo.
- Scaffold del repositorio: `composer.json`/`package.json` raíz, `.gitignore`,
  `.editorconfig`, `README.md`, `ROADMAP.md`, `docs/Architecture.md`.
- `docs/handoff-nd-platform.md`: compilación de arquitectura, decisiones y lecciones
  reales de ND Platform, como base de diseño para este proyecto.
- `dnorte-core` (plugin): `Application`, `Container` (autowiring por reflexión),
  `Config`, `HookManager`, `EventDispatcher`, `ServiceProvider` base y
  `CoreServiceProvider`. Arranque enganchado a `after_setup_theme` (prioridad 5) en vez
  de `plugins_loaded`, para que el tema activo tenga ocasión de registrarse en el filtro
  `dnorte_core/providers` antes de que la aplicación arranque.
- `dnorte-theme` (tema): bootstrap (`functions.php`, `style.css`), `ThemeServiceProvider`
  (theme supports, menús, encolado de assets), `header.php`/`footer.php`/`index.php`,
  modo oscuro con script anti-parpadeo inline, build de assets con Vite/Sass.
- `phpcs.xml.dist`/`phpstan.neon.dist`/`phpunit.xml.dist` propios por paquete (WPCS +
  PHPStan nivel máximo + PHPUnit 10, mismos criterios documentados que ND Platform:
  camelCase en vez de snake_case, sin Yoda conditions, excepciones internas sin escapar).
- `dnorte-core`: `HookManager::doAction()`/`applyFilters()` — faltaba la mitad del
  wrapper tipado de WordPress (solo tenía addAction/addFilter).
- Suite de pruebas unitarias PHPUnit (Brain Monkey): 27 pruebas para `Container`
  (autowiring, bindings, singleton, errores), `Config` (dot notation, `loadDirectory()`),
  `HookManager` (registro diferido, flush, remove) y `EventDispatcher` en `dnorte-core`;
  3 pruebas para `ThemeServiceProvider` (theme supports, menús, wiring de hooks) en
  `dnorte-theme`.

### Fixed

- `dnorte-theme`: `ThemeServiceProvider::boot()` enganchaba `registerMenus()` a un hook
  `register_nav_menus` que no existe en WordPress core (`register_nav_menus()` es una
  función que se **llama**, no un hook al que engancharse) — los menús nunca se
  registraban y `wp-admin/nav-menus.php` reportaba "tu tema no soporta menús". Encontrado
  en la primera verificación en navegador contra un WordPress real (no por ningún test).
  Corregido llamando `registerMenus()` desde `after_setup_theme`, igual que
  `registerThemeSupports()`.
- `dnorte-core`: `Application::resolveProviderClasses()` llamaba a `apply_filters()`
  directamente en vez de pasar por `HookManager` — violaba el principio de arquitectura
  que el propio `docs/Architecture.md` documenta ("único punto de acceso a WordPress").
  Encontrado por PHPCS (`WordPress.NamingConventions.ValidHookName`, el mismo tipo de
  falso positivo que desaparece cuando el hook pasa por el wrapper, como ya hacía ND).
  De paso, `Application` dejó de repreguntarle al contenedor por `Config`/`HookManager`
  en cada método interno (`Container::get()` devuelve `mixed` sin generics — PHPStan no
  podía verificar las llamadas encadenadas) y ahora usa variables tipadas locales,
  mismo patrón que `NDCore\Application`.

### Verified

- `composer install` y `npm install && npm run build` en verde en un entorno limpio.
- WordPress real (descarga oficial, base de datos MariaDB local nueva) con `dnorte-core`
  y `dnorte-theme` instalados vía symlink, activados con WP-CLI: front-end cargando
  (CSS/JS de Vite servidos, `data-theme` correcto, modo oscuro sin parpadeo confirmado
  cambiando `localStorage`), dashboard y pantallas de administración (Plugins, Temas,
  Menús) sin errores, `debug.log` vacío en todo el recorrido (`WP_DEBUG`/`WP_DEBUG_LOG`
  activos).
- `composer run check` (PHPCS/WPCS + PHPStan nivel máximo + PHPUnit) en verde en ambos
  paquetes: `dnorte-core` (27 pruebas, 38 aserciones) y `dnorte-theme` (3 pruebas).
  Reverificado en el WordPress real de desarrollo tras el refactor de `Application`
  (variables tipadas en vez de `Container::get()` repetido) y `HookManager`
  (`doAction()`/`applyFilters()` nuevos): front-end y `wp-admin/nav-menus.php` sin
  errores, `debug.log` vacío.
- `tools/build/package.sh` verificado de punta a punta: ambos `.zip` generados e
  instalados vía WP-CLI (`wp plugin install`/`wp theme install`, el mismo flujo que
  "Subir plugin"/"Subir tema" en wp-admin) en el WordPress de desarrollo, reemplazando
  los symlinks previos — plugin y tema activos como directorios reales (no symlinks),
  front-end en modo claro y oscuro, dashboard, Plugins y Temas sin errores, `debug.log`
  vacío en todo el recorrido.
