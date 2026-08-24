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

## v0.1.0-alpha.8 — Sitemap de Google News

- [x] `dnorte-core`: `Seo\Sitemap\NewsSitemapController` — sirve `/sitemap-news.xml`
      con namespace `news:`, artículos publicados en las últimas 48h (configurable,
      `config/seo.php`), límite de 1000 URLs (el máximo real de Google News).
      `render()` puro (XML a partir de datos ya resueltos, `XMLWriter`) separado de
      `recentArticleData()` (la única parte que toca `WP_Query`/`WP_Post`).
- [x] `dnorte-core`: `Seo\Robots\RobotsTxtBuilder` ahora añade también la directiva
      `Sitemap:` de `/sitemap-news.xml` en `robots.txt`, junto a la de
      `wp-sitemap.xml`.
- [x] `dnorte-core`: `SeoServiceProvider` registra la rewrite rule (`init`), el query
      var (`query_vars`) y el renderizado (`parse_query`, prioridad 1) del sitemap de
      noticias. `dnorte-core.php` intenta `flush_rewrite_rules()` en la activación
      (mejor esfuerzo — mismo caveat de orden de hooks ya documentado en ND Platform:
      puede no alcanzar a incluir la regla nueva, basta con guardar permalinks una
      vez si `/sitemap-news.xml` da 404 justo tras activar).
- [x] 4 pruebas unitarias nuevas para `NewsSitemapController::render()` (incluye
      escapado de un título con `<script>`) y 4 de integración para
      `recentArticleData()`/`render()` de punta a punta (67 unitarias + 27 de
      integración en total en `dnorte-core`).
- [x] `composer run check` y `composer test:integration` en verde.
- [x] Verificado en el WordPress real de desarrollo: `/sitemap-news.xml` vacío sin
      artículos recientes, poblado correctamente tras publicar uno nuevo (`<loc>`,
      `<news:name>`, `<news:language>`, `<news:publication_date>`, `<news:title>`
      todos presentes), `robots.txt` con ambas directivas `Sitemap:`, `debug.log`
      vacío.

## v0.1.0-alpha.9 — Panel de administración y workflow editorial

- [x] `dnorte-core`: infraestructura de menú de administración —
      `Admin\AdminPage`/`Admin\Contracts\RegistersAdminPages`/
      `Providers\AdminMenuServiceProvider` (filtro `dnorte_core/admin_pages`, mismo
      patrón que `dnorte_core/providers`/`dnorte_core/rest_controllers`), registrado
      por defecto en `Application`.
- [x] `dnorte-core`: módulo de workflow editorial — estados de post propios
      (`EditorialStatusRegistrar`), notas editoriales (`EditorialNoteRepository`,
      tabla `dnorte_editorial_notes`), asignación de artículos por postmeta
      (`ArticleAssignmentRepository`), y turnos (`ShiftRepository`, tabla
      `dnorte_shifts`).
- [x] `dnorte-core`: **panel "Turnos" (`ShiftsAdminPage`)** — asignación de roles
      para los periodistas de turno (editor/redactor/community manager en turno,
      configurable en `config/workflow.php`), pedido explícitamente para Diario del
      Norte, sin equivalente en ND Platform.
- [x] `dnorte-core`: `Installer\MigrationRegistry` — lista central de migraciones,
      resuelve el problema de que `register_activation_hook()` corre antes de que
      exista el contenedor/los providers.
- [x] 16 pruebas unitarias nuevas (79 en total) y 11 de integración nuevas (38 en
      total, incluida `MigrationRegistryTest` corriendo el conjunto completo dos
      veces en la misma prueba para verificar idempotencia sin depender de
      supuestos entre invocaciones del arnés de pruebas).
- [x] `composer run check` (90 archivos, 79 pruebas) y `composer test:integration`
      (38 pruebas) en verde.
- [x] **Bug real encontrado y corregido, no por ningún test**:
      `DNORTE_CORE_VERSION` llevaba ocho versiones fija en `0.1.0-alpha.1` —
      ninguna migración añadida desde alpha.2 se habría ejecutado nunca en un sitio
      real ya instalado. Corregido subiendo la constante a `0.1.0-alpha.9` y
      conectando el activation hook / `maybeRunUpgrade()` a `MigrationRegistry::all()`
      — ver "Fixed" en `CHANGELOG.md`.
- [x] Verificado en el WordPress real de desarrollo: menú "Turnos", flujo completo
      de creación de un turno con datos reales (aparece en "En turno ahora" y en
      "Próximos turnos" según el rango de fechas), enlace de eliminación con nonce,
      `debug.log` vacío en todo el recorrido, y confirmación directa por MySQL de
      que la versión instalada y las tablas nuevas se crearon solas vía
      auto-reparación.

## v0.1.0-alpha.10 — Búsqueda interna

- [x] `dnorte-core`: `Search\Fulltext\CreateSearchFulltextIndex` — índice FULLTEXT
      sobre `wp_posts.post_title`/`post_content` (primera migración que altera una
      tabla nativa de WordPress, no una tabla `dnorte_*` propia).
- [x] `dnorte-core`: `Search\SearchQueryModifier` (filtros `posts_search`/
      `posts_orderby`) sustituye el `LIKE`+orden-por-fecha nativo por
      `MATCH ... AGAINST` con ranking por relevancia real, para cualquier
      `WP_Query` de búsqueda. `Search\BooleanModeTermBuilder` (pieza pura) arma la
      sintaxis booleana con `*` por palabra ("empieza por").
- [x] `dnorte-core`: `Database\DatabaseManager::fragment()` — expone
      `wpdb::prepare()` como fragmento SQL para insertar en el filtro de
      WordPress, sin ejecutar la consulta.
- [x] `dnorte-core`: `GET /wp-json/dnorte/v1/search?q=...` — endpoint ligero para
      una caja de búsqueda con sugerencias en vivo, reutiliza el mismo `WP_Query`
      con `s` (y por tanto el mismo ranking) sin duplicar lógica.
- [x] `dnorte-theme`: caja de búsqueda funcional en la cabecera y `search.php`
      (reutiliza `post-card.php`/`the_posts_pagination()`, mismo patrón que
      `archive.php`).
- [x] 7 pruebas unitarias nuevas (88 en total) y 5 de integración nuevas (43 en
      total). `composer run check` y `composer test:integration` en verde.
- [x] **Bug real encontrado y corregido, no relacionado con búsqueda pero
      encontrado al tocar `dnorte-theme`**: `DNORTE_THEME_VERSION` llevaba 9
      versiones hardcodeada en `functions.php`, invalidando la caché del navegador
      de los assets en cada despliegue — corregido leyéndola siempre de la
      cabecera `Version:` de `style.css`. Ver "Fixed" en `CHANGELOG.md`.
- [x] **Hallazgo real de infraestructura de pruebas**: InnoDB no hace visibles las
      filas insertadas en la misma transacción sin confirmar a un
      `MATCH ... AGAINST`, y `WP_UnitTestCase` nunca confirma la transacción
      por-prueba — corregido creando los artículos de fixture en
      `wpSetUpBeforeClass()`. Documentado en `docs/Architecture.md`.
- [x] Verificado en el WordPress real de desarrollo: caja de búsqueda en escritorio
      y móvil (claro/oscuro), búsqueda real de "sector energético" devuelve solo el
      artículo relevante, estado vacío correcto, endpoint REST confirmado con
      `curl`, `debug.log` vacío en todo el recorrido.

## v0.1.0-alpha.11 — Analítica propia

- [x] `dnorte-core`: módulo de analítica propia — "qué se lee", no "quién lee".
      `Analytics\Pageviews\CreatePageviewsTable` (tabla `dnorte_pageviews`: solo
      `post_id`/`referrer_host`/`viewed_at`, sin IP/user-agent/identificador de
      visitante). `Analytics\PageviewBeaconRenderer` (`wp_footer`, sin tocar el
      tema) emite un beacon `navigator.sendBeacon()`, excluyendo al equipo
      editorial (`current_user_can('edit_posts')`).
      `POST /wp-json/dnorte/v1/analytics/pageview` (`Analytics\PageviewController`)
      registra solo artículos publicados.
- [x] `dnorte-core`: `Analytics\PageviewPurger` — purga diaria por WP-Cron de filas
      más antiguas que `analytics.retention_days` (90 por defecto).
- [x] `dnorte-core`: panel "Analítica" (`Analytics\AnalyticsAdminPage`, solo
      lectura) — vistas totales 24h/7d/30d y artículos más vistos en
      `analytics.top_articles_window_days` (7 días).
- [x] 8 pruebas unitarias nuevas (99 en total) y 8 de integración nuevas (51 en
      total). `composer run check` y `composer test:integration` en verde.
- [x] **Bug real encontrado y corregido — primer caso real de dos módulos de admin
      distintos desde `v0.1.0-alpha.9`**: `Admin\AdminPage`/
      `Providers\AdminMenuServiceProvider` anidaban cualquier página nueva bajo la
      de menor `position` de TODA la plataforma, sin importar el módulo —
      invisible con un solo panel (Turnos), habría anidado "Analítica" bajo
      "Turnos" sin relación real entre ambos. Corregido con un `parentSlug`
      explícito por página (`null` = nivel superior propio) en vez de inferirlo
      por posición. Prueba de regresión añadida. Ver "Fixed" en `CHANGELOG.md`.
- [x] Verificado en el WordPress real de desarrollo: "Analítica" como su propia
      entrada de nivel superior (no anidada bajo "Turnos"); beacon real confirmado
      en el HTML servido a un visitante anónimo; `POST` simulado registra la fila
      con solo el dominio del referente; panel muestra vistas totales y el
      artículo correcto en "Artículos más vistos"; `debug.log` vacío en todo el
      recorrido.

## v0.1.0-alpha.12 — Publicidad propia

- [x] `dnorte-core`: los cinco espacios pedidos explícitamente por el cliente
      (`config/ads.php`) — Cabecera (encima de la barra superior, todo el
      sitio), Inicio (debajo del menú, todo el sitio), Top noticia (al iniciar
      el artículo), Intermedio (después del tercer párrafo), Final (al terminar
      el artículo).
- [x] `Ads\Ad`/`Ads\AdRepository` (tabla `dnorte_ads`, un único anuncio activo
      por espacio en v1, con activo/inactivo y ventana de fechas opcional para
      programar una campaña). `Ads\AdSlotRenderer` (HTML sin escapar, marcado de
      terceros por diseño). `Ads\ContentParagraphInjector` (pieza pura) para el
      espacio Intermedio.
- [x] `Providers\AdsServiceProvider`: Cabecera/Inicio vía dos hooks propios y
      mínimos añadidos a `dnorte-theme/header.php`; Top noticia/Intermedio/Final
      enteramente vía el filtro `the_content` de WordPress, sin tocar ninguna
      plantilla del tema.
- [x] Panel "Publicidad" (`Ads\AdsAdminPage`, capacidad `manage_options` — más
      estricta que Turnos/Analítica, por guardar HTML/JS sin filtrar).
- [x] `Support\DatetimeLocalInput` extraído desde `ShiftsAdminPage` (segundo uso
      real) para compartirlo con `AdsAdminPage`.
- [x] 17 pruebas unitarias nuevas (115 en total) y 9 de integración nuevas (60
      en total), incluida una de punta a punta con un bucle de WordPress real
      (`go_to()`) verificando el orden exacto de los tres espacios de artículo.
      `composer run check` y `composer test:integration` en verde.
- [x] Verificado en el WordPress real de desarrollo: los cinco espacios
      configurados desde el panel real y confirmados en la posición correcta —
      incluida la verificación exacta de "después del tercer párrafo" con un
      artículo de 4 párrafos creado para la prueba — en escritorio, móvil y
      ambos modos de color. "Publicidad" como su propia entrada de nivel
      superior en el menú (no anidada). `debug.log` vacío en todo el recorrido.

## v0.1.0-alpha.13 — Publicidad propia: de "un anuncio por espacio" a campañas

Rediseño pedido tras compartir el formulario real de campañas del cliente —
mismos cinco espacios de alpha.12, modelo de datos nuevo.

- [x] `Ads\Campaign`/`Ads\CampaignRepository` (tabla `dnorte_ad_campaigns`,
      reemplaza `dnorte_ads`): una campaña se dirige a varios espacios a la
      vez (no uno solo), con prioridad (para empates entre campañas activas
      en el mismo espacio) y segmentación opcional por categoría.
- [x] Tipo de campaña: HTML/banner propio (como en alpha.12) o **Google
      AdSense nativo** (Client ID + Slot). `AdsServiceProvider::enqueueAdSenseLoader()`
      encola `adsbygoogle.js` una única vez por página vía
      `wp_enqueue_script()`, solo si hace falta.
- [x] Panel "Publicidad" rediseñado: tabla de campañas existentes + un único
      formulario (crear/editar vía `?edit={id}`) con las zonas agrupadas en
      checkboxes — reemplaza los cinco formularios repetidos de alpha.12.
- [x] `Ads\Migrations\CreateAdCampaignsTable`/`DropLegacyAdsTable` — migraciones
      nuevas, la tabla `dnorte_ads` de alpha.12 nunca se reescribe ni se borra
      de `MigrationRegistry` (queda documentada como obsoleta).
- [x] 5 pruebas unitarias nuevas (118 en total) y 6 de integración nuevas (66
      en total). `composer run check` y `composer test:integration` en verde.
- [x] Verificado en el WordPress real de desarrollo: campaña real creada desde
      el formulario dirigida a Cabecera + Inicio a la vez, confirmada
      visualmente en ambos espacios; edición de una campaña existente con
      todos los campos precargados (incluido AdSense); script
      `adsbygoogle.js` confirmado en el HTML servido; los tres espacios de
      artículo siguen funcionando igual que en alpha.12; `debug.log` vacío en
      todo el recorrido.

## v0.1.0-alpha.14 — Publicidad propia: estadísticas, evidencia, informes e historial

Ampliación pedida a partir del panel de campañas real del cliente
(ESTADÍSTICAS/ACCIONES con Desactivar/Subir evidencia/Generar informe/Borrar, y
una pestaña Historial) sobre el modelo de campañas de alpha.13.

- [x] Tipo de campaña "Imagen" (banner propio: URL de imagen + URL de destino).
- [x] Impresiones/clics/CTR por campaña — script de seguimiento compartido
      (`wp_footer`, excluye al equipo editorial), endpoint REST
      (`Ads\CampaignEventController`), incremento atómico en
      `CampaignRepository`.
- [x] Activar/Desactivar de un clic (sin pasar por el formulario completo).
- [x] "Subir evidencia" — adjunta capturas/comprobantes vía la Biblioteca de
      medios nativa de WordPress.
- [x] "Generar informe" — vista imprimible dentro del propio panel
      (Imprimir/Guardar como PDF del navegador, sin PDF generado en servidor).
- [x] Pestaña "Historial" (`Ads\CampaignHistoryRepository`, tabla
      `dnorte_ad_campaign_history`) — quién hizo qué y cuándo a cada campaña,
      conserva el nombre aunque la campaña se borre después.
- [x] 10 pruebas unitarias nuevas (123 en total) y 12 de integración nuevas (72
      en total). `composer run check` y `composer test:integration` en verde.
- [x] **Bug real encontrado en la verificación del navegador**: los enlaces de
      navegación del panel arrastraban los parámetros de una acción de
      escritura ya ejecutada, re-ejecutándola en un clic posterior. Corregido
      con un único punto de limpieza de URL (`AdsAdminPage::cleanBaseUrl()`).
      Ver "Fixed" en `CHANGELOG.md`.
- [x] Verificado en el WordPress real de desarrollo: flujo completo de una
      campaña real (activar/desactivar, evidencia real subida y listada,
      informe con todos los campos, impresión/clic simulados reflejados de
      inmediato en "Estadísticas" con el formato exacto pedido), script de
      seguimiento confirmado para un visitante anónimo y ausente para el
      equipo editorial, `debug.log` vacío en todo el recorrido.

## v0.1.0-alpha.15 — Publicidad propia: seis tipos de campaña, formulario simplificado

Pedido a partir de la lista real de tipos del cliente (adsense/gam/html/image/
video/sponsored).

- [x] Tres tipos nuevos: **Google Ad Manager** (ruta de la unidad + tamaños,
      genera `googletag.defineSlot()`, `gpt.js` encolado una única vez por
      página igual que AdSense), **Vídeo** (banner propio autoreproducido) y
      **Contenido patrocinado** (imagen + texto descriptivo).
- [x] Formulario reducido al tipo elegido: cada campo se muestra solo si
      corresponde al tipo seleccionado (`data-ad-fields-for` + un único
      `<script>` vainilla, sin dependencias nuevas) — antes se mostraban todos
      los campos de todos los tipos a la vez.
- [x] 4 pruebas unitarias nuevas (127 en total) y 3 de integración nuevas (75
      en total). `composer run check` y `composer test:integration` en verde.
- [x] Verificado en el WordPress real de desarrollo: los seis tipos en el orden
      pedido, revelado correcto de campos al cambiar el tipo (HTML/GAM/
      Contenido patrocinado probados), campaña GAM real confirmada en el HTML
      servido (`defineSlot` con la ruta/tamaños correctos, `gpt.js` encolado),
      campaña de contenido patrocinado real verificada visualmente, `debug.log`
      vacío en todo el recorrido.

## Próximas versiones (por decidir)

Alcance técnico restante, confirmado explícitamente por el cliente: IA — con la
misma pregunta previa de si un plugin ya probado o una función nativa de
WordPress lo resuelve sin construir nada nuevo (ver `docs/handoff-nd-platform.md`
§8). El workflow editorial, el panel de turnos, la búsqueda interna, la
analítica propia y la publicidad propia ya se cerraron (alpha.9–alpha.15). En lo
estético: guía de marca real de Diario del Norte (sustituir el color de acento
placeholder), y posible logo/isotipo en vez de branding solo textual.
