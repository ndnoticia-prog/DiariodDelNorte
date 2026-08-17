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
- [ ] Suite de pruebas unitarias PHPUnit (Brain Monkey) para `Container`, `Config`,
      `HookManager`, `EventDispatcher`.
- [ ] `phpcs.xml`/`phpstan.neon` propios (aún no existen) para que `composer run check`
      funcione de punta a punta — hoy solo se verificó `composer install`/`npm run build`.
- [ ] Script de empaquetado (`tools/build/package.sh` o equivalente) que genere
      `dnorte-core-0.1.0-alpha.1.zip`/`dnorte-theme-0.1.0-alpha.1.zip` instalables.
- [x] Verificación con el toolchain instalado: `composer install` y
      `npm install && npm run build` en verde, generan `dist/app.css`/`dist/app.js`.
- [x] Activación verificada en un WordPress real vía WP-CLI (no solo revisión de código,
      no un zip todavía — symlinks de desarrollo): plugin y tema activos, front-end
      cargando (assets de Vite servidos), modo oscuro funcionando sin parpadeo, dashboard
      y pantallas de administración (Plugins, Temas, Menús) sin errores, `debug.log`
      vacío. Encontrado y corregido en el proceso: `ThemeServiceProvider` enganchaba
      `registerMenus()` a un hook inexistente (`register_nav_menus` no es una action de
      WordPress) — ver "Fixed" en `CHANGELOG.md`.

## Próximas versiones (por decidir)

Alcance a definir según necesidad real de Diario del Norte, no por paridad con ND
Platform (ver `docs/handoff-nd-platform.md` §8). Candidatos, en orden probable de
prioridad: base de datos/migraciones + REST API básica, SEO técnico (Schema.org,
OpenGraph, sitemap), multimedia (WebP/AVIF, imagen destacada), y solo después evaluar
publicidad propia, analítica propia, IA, búsqueda interna y workflow editorial — cada
uno preguntando primero si un plugin ya probado o una función nativa de WordPress lo
resuelve sin construir nada nuevo.
