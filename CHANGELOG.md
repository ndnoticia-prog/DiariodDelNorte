# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

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
