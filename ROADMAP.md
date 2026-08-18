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

## v0.1.0-alpha.4 — SEO técnico

- [x] `dnorte-core`: `Seo\Context\SeoContext`/`SeoContextResolver` (única fuente de
      verdad por página: singular, home, archivo, búsqueda, 404).
- [x] `dnorte-core`: meta tags en `wp_head` — robots (con `max-image-preview:large`
      para elegibilidad en Discover), canonical, OpenGraph, Twitter Cards
      (`Seo\Meta\RobotsMetaBuilder`/`OpenGraphBuilder`/`TwitterCardBuilder`/`MetaTagsRenderer`).
- [x] `dnorte-core`: Schema.org JSON-LD como un único `@graph` — `Organization`,
      `WebSite` (con `SearchAction`), `NewsArticle`, `BreadcrumbList`
      (`Seo\Schema\*`, `Seo\Breadcrumbs\BreadcrumbBuilder`).
- [x] `dnorte-core`: `robots.txt` con directiva `Sitemap:` apuntando al
      `wp-sitemap.xml` nativo de WordPress (`Seo\Robots\RobotsTxtBuilder`) — sin
      reimplementar el sitemap general.
- [x] `dnorte-core`: `SeoServiceProvider` registrado por defecto en `Application`.
- [x] Suite de pruebas unitarias PHPUnit (Brain Monkey) para las piezas puras
      (`RobotsMetaBuilder`, `OpenGraphBuilder`, `TwitterCardBuilder`,
      `MetaTagsRenderer`, `OrganizationSchema`, `WebSiteSchema`,
      `BreadcrumbListSchema`, `SchemaOutput`, `RobotsTxtBuilder`) — 20 pruebas nuevas
      (52 en total en `dnorte-core`).
- [x] Pruebas de integración reales para `SeoContextResolver`, `BreadcrumbBuilder` y
      `ArticleSchema` (dependen de `WP_Post`/`WP_Term` reales) — 9 pruebas nuevas (23
      en total), aprovechando la infraestructura de `v0.1.0-alpha.3`. Encontrado en el
      proceso (por la propia prueba, no por revisión manual): `get_the_archive_title()`
      antepone "Category: "/"Tag: " al nombre — correcto para el `<title>` SEO, mala
      UX para una miga de pan; corregido usando el término directamente — ver "Fixed"
      en `CHANGELOG.md`.
- [x] `composer run check` (52 pruebas) y `composer test:integration` (23 pruebas) en
      verde.
- [x] `.zip` regenerado e instalado sobre el WordPress real de desarrollo: meta tags,
      OpenGraph, Twitter Cards, JSON-LD (`Organization`+`WebSite`+`NewsArticle`+
      `BreadcrumbList` verificado en un artículo real) y `robots.txt` con la directiva
      `Sitemap:` funcionando, `debug.log` vacío.

## v0.1.0-alpha.5 — Multimedia

- [x] `dnorte-core`: `Media\ModernFormatConverter` — filtro nativo
      `image_editor_output_format` (WordPress 5.8+), formato preferido configurable
      (`config/media.php`, `media.modern_format`: `webp`/`avif`/desactivado), `avif`
      cae a `webp` si el servidor no lo soporta, detección real de soporte GD
      (`function_exists('imagewebp'/'imageavif')`).
- [x] `dnorte-core`: `Media\FeaturedImageSize` — tamaño `dnorte-featured` (1200×675,
      requisito de Google Discover), registrado en `after_setup_theme`.
      `Seo\Context\SeoContextResolver` actualizado para preferirlo (con fallback
      explícito a `large` si no existe una versión de ese tamaño para el post).
- [x] `dnorte-core`: `Providers\MediaServiceProvider`, registrado por defecto en
      `Application`.
- [x] `patchwork.json` (`redefinable-internals: function_exists`) — necesario para que
      Brain Monkey pueda interceptar `function_exists()` en las pruebas unitarias del
      conversor de formato.
- [x] 8 pruebas unitarias nuevas (`ModernFormatConverter`, `FeaturedImageSize`,
      `MediaServiceProvider`) — 63 en total en `dnorte-core`. Sin pruebas de
      integración adicionales: `ModernFormatConverter`/`FeaturedImageSize` no
      dependen de `WP_Post` (se cubren completas con Brain Monkey); el
      comportamiento del tamaño de imagen destacada ya lo ejercita
      `SeoContextResolverTest` desde alpha.4.
- [x] `composer run check` (63 pruebas) y `composer test:integration` (23 pruebas) en
      verde.
- [x] Verificado en el WordPress real de desarrollo: imagen real subida vía WP-CLI,
      metadata del adjunto confirma tamaños intermedios generados en `.webp`
      (`mime-type: image/webp`), Biblioteca de medios renderizando el `.webp` sin
      errores, `debug.log` vacío. El tamaño `dnorte-featured` correctamente no se
      genera para una fuente más pequeña que 1200×675 (WordPress evita
      ampliar-y-recortar) — comportamiento esperado, no un bug.

## v0.1.0-alpha.6 — Identidad visual (fase 1: cabecera y sistema de diseño)

Con la base técnica cerrada (alpha.1–alpha.5), arranca la fase estética — la plantilla
seguía siendo el placeholder mínimo del scaffold inicial.

- [x] `dnorte-theme`: sistema de diseño ampliado en `app.scss` — tokens de color
      (superficie, texto atenuado, enlaces visitados), tipografía (titulares en serif
      del sistema, cuerpo en sans del sistema — deliberadamente sin cargar fuentes
      externas mientras no haya guía de marca real que lo exija), escala de espaciado,
      radios/sombras. El color de acento (`--color-accent`) sigue siendo un
      placeholder explícito a la espera de la marca real de Diario del Norte.
- [x] `dnorte-theme`: cabecera rediseñada — barra superior con fecha estilo cabecera
      de diario, masthead con línea de acento, navegación con estados hover/focus,
      **botón visible para alternar modo oscuro** (antes solo se detectaba
      automático por preferencia del sistema — sin control manual para el usuario) y
      **menú móvil con botón hamburguesa** (antes el header no era usable en pantallas
      angostas: los enlaces de navegación no tenían forma de mostrarse/ocultarse).
- [x] `dnorte-theme`: pie de página con layout de columnas y jerarquía tipográfica
      propia.
- [x] `dnorte-theme`: `assets/js/app.js` — interacción del botón de tema (sincroniza
      `aria-label` con el estado real al cargar) y del menú móvil.
- [x] Dos bugs de CSS reales encontrados en la propia verificación visual, no por
      revisión de código: `text-transform: capitalize` en la fecha de cabecera
      capitalizaba cada palabra ("De August De"), rompiendo las reglas de
      capitalización del español (corregido quitándolo); `.header-controls` sin
      `flex-wrap` combinado con el `<nav>` en `flex-basis: 100%` comprimía el botón
      de menú móvil hasta volverlo invisible (corregido con `flex-wrap: wrap` +
      `flex-shrink: 0` + tamaño explícito del ícono SVG, que tampoco lo tenía).
- [x] `composer run check` (3 pruebas, sin cambios de lógica PHP nueva que testear —
      esta versión es principalmente CSS/JS) en verde en `dnorte-theme`.
- [x] Verificado en el WordPress real de desarrollo, en escritorio y móvil (375px),
      claro y oscuro, con un menú real asignado: masthead, navegación, toggle de tema
      y menú móvil funcionando (confirmado el toggle disparando el evento `click`
      real del botón — no solo revisión visual), `debug.log` vacío.

## v0.1.0-alpha.7 — Identidad visual (fase 2: portada, artículo y archivo)

- [x] `dnorte-theme`: `Content\HomeContentProvider` — una sola `WP_Query` repartida en
      hero (más reciente), última hora (siguientes 3) y más noticias (siguientes 6).
- [x] `dnorte-theme`: `front-page.php` con bloques reales — `template-parts/blocks/hero.php`,
      `breaking.php`, `latest-grid.php` — y `template-parts/post-card.php` reutilizable
      (portada y archivo comparten la misma tarjeta).
- [x] `dnorte-theme`: `single.php` — tipografía editorial (columna de 72ch), kicker de
      categoría, imagen destacada, byline con autor/fecha, y **migas de pan visibles**
      (`template-parts/breadcrumbs.php`, usa `Seo\Breadcrumbs\BreadcrumbBuilder` de
      dnorte-core — el mismo origen de datos que ya alimenta el `BreadcrumbList` de
      Schema.org desde alpha.4, para que el HTML visible y el JSON-LD nunca puedan
      divergir).
- [x] `dnorte-theme`: `archive.php` — cabecera con el nombre del término (no
      `get_the_archive_title()`, mismo criterio ya aplicado en `BreadcrumbBuilder`) +
      cuadrícula de tarjetas + paginación.
- [x] `dnorte-theme`: `comments.php` — sin esta plantilla WordPress emite un aviso de
      deprecación en cada artículo con comentarios abiertos; encontrado en la primera
      verificación real en navegador, mismo tipo de hallazgo que ND Platform ya
      documentó.
- [x] Infraestructura de pruebas de integración para `dnorte-theme`, reutilizando el
      mismo PHPUnit 9 aislado y compartido de `tools/wp-tests/phpunit9/` (mapeo de
      `DNorteTheme\` añadido junto al de `DNorteCore\`, mismo patrón que el
      meta-proyecto compartido de ND Platform). 4 pruebas de integración reales para
      `HomeContentProvider`.
- [x] Bug real encontrado en la verificación, no en revisión de código:
      `tools/build/package.sh` copiaba una lista fija de archivos del tema
      (`style.css`/`functions.php`/`header.php`/`footer.php`/`index.php`) — al añadir
      `front-page.php`, `single.php`, `archive.php` y `template-parts/`, el `.zip`
      generado seguía sin incluirlos y la portada real caía al `index.php` de
      fallback sin que ningún test lo detectara. Corregido copiando todos los `.php`
      de la raíz del tema automáticamente (con `phpstan-bootstrap.php` excluido
      explícitamente, por ser solo de desarrollo) en vez de mantener la lista a mano.
- [x] `composer run check` (3 unitarias) y `composer test:integration` (4 pruebas) en
      verde en `dnorte-theme`; `dnorte-core` reverificado sin cambios (63 unitarias +
      23 de integración).
- [x] Verificado en el WordPress real de desarrollo con contenido real (9 artículos,
      3 categorías, imágenes destacadas, autor asignado): portada con hero/última
      hora/cuadrícula, artículo individual con breadcrumbs y modo oscuro, archivo de
      categoría con título limpio — `debug.log` vacío en todo el recorrido, incluido
      el aviso de `comments.php` que ya no aparece.

## Próximas versiones (por decidir)

Alcance técnico restante, no por paridad con ND Platform (ver
`docs/handoff-nd-platform.md` §8): sitemap de Google News/Discover, y solo después
evaluar publicidad propia, analítica propia, IA, búsqueda interna y workflow
editorial — cada uno preguntando primero si un plugin ya probado o una función nativa
de WordPress lo resuelve sin construir nada nuevo. En lo estético: guía de marca real
de Diario del Norte (sustituir el color de acento placeholder), y posible logo/isotipo
en vez de branding solo textual.
