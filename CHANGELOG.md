# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

## [Unreleased] — v0.1.0-alpha.1

### Added

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

### Fixed

- `dnorte-theme`: `ThemeServiceProvider::boot()` enganchaba `registerMenus()` a un hook
  `register_nav_menus` que no existe en WordPress core (`register_nav_menus()` es una
  función que se **llama**, no un hook al que engancharse) — los menús nunca se
  registraban y `wp-admin/nav-menus.php` reportaba "tu tema no soporta menús". Encontrado
  en la primera verificación en navegador contra un WordPress real (no por ningún test).
  Corregido llamando `registerMenus()` desde `after_setup_theme`, igual que
  `registerThemeSupports()`.

### Verified

- `composer install` y `npm install && npm run build` en verde en un entorno limpio.
- WordPress real (descarga oficial, base de datos MariaDB local nueva) con `dnorte-core`
  y `dnorte-theme` instalados vía symlink, activados con WP-CLI: front-end cargando
  (CSS/JS de Vite servidos, `data-theme` correcto, modo oscuro sin parpadeo confirmado
  cambiando `localStorage`), dashboard y pantallas de administración (Plugins, Temas,
  Menús) sin errores, `debug.log` vacío en todo el recorrido (`WP_DEBUG`/`WP_DEBUG_LOG`
  activos).
