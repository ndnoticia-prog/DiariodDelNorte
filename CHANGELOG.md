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
