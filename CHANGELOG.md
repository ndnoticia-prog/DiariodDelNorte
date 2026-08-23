# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

## [Unreleased] — v0.1.0-alpha.11

### Added

- `dnorte-core`: módulo de analítica propia — pensado para "qué se lee", no "quién
  lee" (ver "Analítica propia" en `docs/Architecture.md`). `Analytics\Pageviews\CreatePageviewsTable`
  crea `dnorte_pageviews` (`post_id`, `referrer_host` — solo el dominio del
  referente, nunca la URL completa —, `viewed_at`; sin IP, sin user-agent, sin
  identificador de visitante). `Analytics\PageviewBeaconRenderer` (enganchado a
  `wp_footer`, sin tocar el tema) emite un `<script>` mínimo con
  `navigator.sendBeacon()`, excluyendo al propio equipo editorial
  (`current_user_can('edit_posts')`) para no contaminar las estadísticas con sus
  propias vistas previas/revisiones. `POST /wp-json/dnorte/v1/analytics/pageview`
  (`Analytics\PageviewController`) recibe el beacon y solo registra artículos
  publicados.
- `dnorte-core`: `Analytics\PageviewPurger` — purga diaria por WP-Cron
  (`dnorte_core/analytics_purge`) de filas más antiguas que
  `analytics.retention_days` (90 por defecto); higiene de tamaño de tabla, no un
  requisito de privacidad (no hay datos personales que purgar).
- `dnorte-core`: panel "Analítica" (`Analytics\AnalyticsAdminPage`) — vistas
  totales (24h/7d/30d) y artículos más vistos en `analytics.top_articles_window_days`
  (7 días por defecto), de solo lectura.
- `config/analytics.php`: tipos de contenido rastreados, ventana del ranking de
  más vistos, días de retención.
- 8 pruebas unitarias nuevas (99 en total en `dnorte-core`, incluida la de
  regresión de `AdminMenuServiceProvider` de abajo) y 8 de integración nuevas
  (51 en total).

### Fixed

- **`dnorte-core`: `Admin\AdminPage`/`Providers\AdminMenuServiceProvider` anidaban
  cualquier página de administración nueva bajo la de menor `position` de TODA la
  plataforma, sin importar de qué módulo viniera.** Invisible mientras solo
  existía el panel de Turnos (un único `RegistersAdminPages` registrado); al
  intentar sumar el panel de Analítica como segundo módulo con página propia,
  habría quedado anidado bajo "Turnos" sin ninguna relación real entre ambos — el
  primer caso real de dos módulos de admin distintos desde que se construyó esta
  infraestructura en `v0.1.0-alpha.9`. Corregido dándole a `AdminPage` un
  `parentSlug` explícito (`null` = su propia entrada de nivel superior, un slug =
  submenú de esa página concreta) en vez de inferirlo por posición. Prueba de
  regresión añadida en `AdminMenuServiceProviderTest`. Ver "Menú de
  administración" en `docs/Architecture.md`.

### Verified

- `composer run check` en `dnorte-core` (0 errores PHPCS, 0 errores PHPStan nivel
  máximo, 99 pruebas unitarias) y `composer test:integration` (51 pruebas) en
  verde.
- WordPress real de desarrollo: "Analítica" aparece como su propia entrada de
  nivel superior en el menú (no anidada bajo "Turnos"); artículo real visitado
  como visitante anónimo emite el beacon correcto (confirmado en el HTML servido);
  `POST` simulado al endpoint devuelve 204 y registra la fila con solo el dominio
  del referente (`www.google.com`, nunca la URL completa con el término de
  búsqueda); panel "Analítica" muestra vistas totales y el artículo correcto en
  "Artículos más vistos"; `debug.log` vacío en todo el recorrido.

## [Unreleased] — v0.1.0-alpha.10

### Added

- `dnorte-core`: módulo de búsqueda interna. `Search\Fulltext\CreateSearchFulltextIndex`
  añade un índice FULLTEXT sobre `wp_posts.post_title`/`post_content` (primera
  migración de la plataforma que altera una tabla nativa de WordPress en vez de
  crear una tabla `dnorte_*` propia — comprueba `SHOW INDEX` antes de `ALTER TABLE`
  porque `ADD FULLTEXT` no admite `IF NOT EXISTS`). `Search\SearchQueryModifier` se
  engancha a los filtros nativos `posts_search`/`posts_orderby` (para cualquier
  `WP_Query` con `is_search()`, no solo la consulta principal) y sustituye el
  `LIKE '%término%'` + orden por fecha de WordPress core por un
  `MATCH ... AGAINST (... IN BOOLEAN MODE)` con ranking por relevancia real.
  `Search\BooleanModeTermBuilder` (pieza pura) traduce el término del visitante a
  esa sintaxis, con `*` final por palabra para que funcione como "empieza por".
- `dnorte-core`: `Database\DatabaseManager::fragment()` — excepción documentada al
  principio de "único punto de acceso a $wpdb": expone `wpdb::prepare()` como
  fragmento SQL sin ejecutar la consulta, porque `posts_search`/`posts_orderby`
  esperan de vuelta eso, no un resultado.
- `dnorte-core`: `GET /wp-json/dnorte/v1/search?q=...` (`Search\InternalSearchController`)
  — endpoint ligero para una caja de búsqueda con sugerencias en vivo (título,
  extracto, URL, fecha), reutiliza el mismo `WP_Query` con `s` y por tanto el mismo
  ranking por relevancia sin duplicar lógica.
- `dnorte-theme`: caja de búsqueda funcional en la cabecera (`searchform.php`,
  `get_search_form()`) y `search.php` — página de resultados que reutiliza
  `template-parts/post-card.php` y `the_posts_pagination()`, mismo patrón que
  `archive.php`.
- `config/search.php`: tipos de contenido cubiertos, longitud mínima de término
  (3 caracteres — más corto que eso MySQL ya ignora la palabra en el índice
  FULLTEXT por defecto) y límite de resultados del endpoint REST.
- 7 pruebas unitarias nuevas (88 en total en `dnorte-core`) y 5 de integración
  nuevas (43 en total).

### Fixed

- `dnorte-theme`: `DNORTE_THEME_VERSION` estaba hardcodeada en `functions.php`
  (`0.1.0-alpha.1`, nunca actualizada pese a 9 subidas de versión en `style.css`) y
  se usa como cadena de caché (`?ver=...`) de `dist/app.css`/`dist/app.js` —
  mismo tipo de "versión olvidada" que `DNORTE_CORE_VERSION` en `v0.1.0-alpha.9`,
  aquí con el agravante de invalidar la caché del navegador en cada despliegue.
  Corregido leyéndola siempre de `wp_get_theme()->get('Version')` (la cabecera
  `Version:` de `style.css`, única fuente de verdad) en vez de un valor fijo.

### Verified

- `composer run check` en `dnorte-core` (0 errores PHPCS, 0 errores PHPStan nivel
  máximo, 88 pruebas unitarias) y `composer test:integration` (43 pruebas) en
  verde.
- Hallazgo real durante la primera corrida de las pruebas de integración de
  búsqueda (no un bug de `SearchQueryModifier`): InnoDB no hace visibles las filas
  insertadas en la misma transacción sin confirmar a un `MATCH ... AGAINST`, y
  `WP_UnitTestCase` envuelve cada prueba en una transacción que nunca se confirma
  — corregido moviendo los artículos de fixture a `wpSetUpBeforeClass()` (documentado
  en `docs/Architecture.md`).
- WordPress real de desarrollo: caja de búsqueda en la cabecera (escritorio, móvil
  375px, claro y oscuro), búsqueda real de "sector energético" devuelve solo el
  artículo relevante (no un `LIKE` que hubiera traído coincidencias parciales
  sueltas), estado vacío ("No se encontraron artículos con ese término.") con un
  término sin resultados, endpoint REST verificado con `curl` (permalinks bonitos y
  `?rest_route=`), `debug.log` vacío en todo el recorrido.

## [Unreleased] — v0.1.0-alpha.9

### Added

- `dnorte-core`: infraestructura de menú de administración — `Admin\AdminPage` (value
  object, `render` tipado `Closure` porque `callable` no es un tipo de propiedad
  legal en PHP), contrato `Admin\Contracts\RegistersAdminPages`,
  `Providers\AdminMenuServiceProvider` (filtro `dnorte_core/admin_pages`, mismo
  patrón que `dnorte_core/providers` y `dnorte_core/rest_controllers`: resuelve cada
  clase vía el contenedor, ordena por `position`, la primera página se registra con
  `add_menu_page()` y el resto como submenú).
- `dnorte-core`: módulo de workflow editorial —
  `Workflow\Status\EditorialStatusRegistrar` (dos estados de post propios,
  `dnorte_in_review`/`dnorte_needs_changes`, no públicos); `Workflow\Notes\EditorialNote`
  + `EditorialNoteRepository` (tabla `dnorte_editorial_notes`); `Workflow\Assignments\ArticleAssignmentRepository`
  (asignación de un artículo a un periodista vía postmeta, `_dnorte_assigned_to`);
  `Workflow\Shifts\Shift` + `ShiftRepository` (tabla `dnorte_shifts` — periodista, rol,
  inicio/fin, notas).
- `dnorte-core`: **`Workflow\Shifts\ShiftsAdminPage`** — panel "Turnos" en el menú de
  administración (`edit_others_posts`): quién está de turno ahora mismo, formulario
  para asignar un turno nuevo (`wp_dropdown_users()`, rol de turno desde
  `config/workflow.php`, rango de fechas), tabla de próximos turnos con eliminación
  (`wp_nonce_url()`). Es el panel de asignación de roles para los periodistas de
  turno pedido explícitamente para Diario del Norte — no existía en ND Platform.
- `dnorte-core`: `Installer\MigrationRegistry` — lista estática central de
  migraciones, usada tanto por el hook de activación (`register_activation_hook()`,
  que corre antes de que `Application::boot()` — y por tanto el contenedor y los
  providers — exista) como por `CoreServiceProvider::maybeRunUpgrade()`.
- 16 pruebas unitarias nuevas (`AdminPage`, `AdminMenuServiceProvider`,
  `EditorialStatusRegistrar`, `ArticleAssignmentRepository`, `Shift`,
  `WorkflowServiceProvider`) — 79 en total en `dnorte-core`. 11 pruebas de
  integración nuevas (`EditorialNoteRepository`, `ShiftRepository`,
  `ShiftsAdminPage`, `MigrationRegistry` — esta última corre el conjunto completo de
  migraciones dos veces en la misma prueba para verificar idempotencia de forma
  autocontenida) — 38 en total.

### Fixed

- **`dnorte-core`: `DNORTE_CORE_VERSION` (y la cabecera `Version:` del plugin)
  llevaban ocho versiones (alpha.2–alpha.8) fijas en `0.1.0-alpha.1`.**
  `CoreServiceProvider::maybeRunUpgrade()` solo corre `Installer::install()` cuando
  la versión instalada guardada en `wp_options` difiere de esa constante — al no
  cambiar nunca, ninguna migración añadida desde alpha.2 se habría ejecutado jamás
  en un sitio real que ya tuviera `dnorte-core` instalado y actualizado en caliente
  (activar/desactivar no lo habría disparado tampoco: el propio activation hook
  también dependía de la lista de migraciones, que hasta ahora era un array vacío
  hardcodeado). No detectado por ningún test — los tests de integración corren
  siempre contra una base de datos de pruebas recién creada. Encontrado al investigar
  por qué `MigrationRegistryTest` no se comportaba como se esperaba, verificando el
  estado real de la base de datos con `mysql` directamente. Corregido: la cabecera y
  la constante se subieron a `0.1.0-alpha.9`, el activation hook y
  `maybeRunUpgrade()` ahora usan `MigrationRegistry::all()` en vez de un array vacío,
  y se verificó en el WordPress real de desarrollo que el mecanismo de
  autoreparación en `init` crea las tablas nuevas solo, sin desactivar/reactivar.
  Disciplina a mantener de aquí en adelante: toda migración nueva exige subir
  `DNORTE_CORE_VERSION`, no solo añadirse a `MigrationRegistry`.

### Verified

- `composer run check` en `dnorte-core` (90 archivos, 0 errores PHPCS, 0 errores
  PHPStan nivel máximo, 79 pruebas unitarias) y `composer test:integration` (38
  pruebas) en verde.
- WordPress real de desarrollo: menú "Turnos" visible en el admin con el ícono
  correcto; panel completo (En turno ahora / formulario de asignación / próximos
  turnos) verificado con datos reales — turno creado con periodista, rol e intervalo
  de fechas reales, aparece correctamente en "Próximos turnos" y, con un intervalo
  que cubre el momento actual, en "En turno ahora"; enlace "Eliminar" con nonce
  presente; `debug.log` sin cambios (vacío) durante todo el flujo. Confirmado por
  consulta directa a MySQL que `dnorte_core_installed_version` quedó en
  `0.1.0-alpha.9` y que las tablas `wp_dnorte_editorial_notes`/`wp_dnorte_shifts` se
  crearon solas vía el mecanismo de auto-reparación, sin reinstalar el plugin.

## [Unreleased] — v0.1.0-alpha.8

### Added

- `dnorte-core`: `Seo\Sitemap\NewsSitemapController` — sirve `/sitemap-news.xml` con
  namespace `news:`, artículos de las últimas 48h (`config/seo.php`), límite de 1000
  URLs. `render()` puro (`XMLWriter`) separado de `recentArticleData()` (la parte que
  toca `WP_Query`/`WP_Post`).
- `dnorte-core`: `RobotsTxtBuilder` añade también la directiva `Sitemap:` de
  `/sitemap-news.xml`.
- `dnorte-core`: `SeoServiceProvider` registra la rewrite rule, el query var y el
  renderizado del sitemap de noticias; `dnorte-core.php` intenta
  `flush_rewrite_rules()` en la activación (mejor esfuerzo).
- 4 pruebas unitarias + 4 de integración nuevas (67 + 27 en total en `dnorte-core`).

### Verified

- `composer run check` y `composer test:integration` en verde.
- WordPress real de desarrollo: `/sitemap-news.xml` vacío sin artículos recientes,
  poblado correctamente tras publicar uno nuevo, `robots.txt` con ambas directivas
  `Sitemap:`, `debug.log` vacío.

## [Unreleased] — v0.1.0-alpha.7

### Added

- `dnorte-theme`: `Content\HomeContentProvider` (una `WP_Query`, repartida en hero /
  última hora / más noticias).
- `dnorte-theme`: `front-page.php` con bloques reales (`hero.php`, `breaking.php`,
  `latest-grid.php`) y `template-parts/post-card.php` reutilizable.
- `dnorte-theme`: `single.php` — tipografía editorial, kicker, imagen destacada,
  byline, migas de pan visibles (`template-parts/breadcrumbs.php`, mismo origen de
  datos que el `BreadcrumbList` de Schema.org).
- `dnorte-theme`: `archive.php` — cabecera con el nombre del término + cuadrícula +
  paginación.
- `dnorte-theme`: `comments.php`.
- Infraestructura de pruebas de integración para `dnorte-theme`, compartiendo el
  PHPUnit 9 aislado de `tools/wp-tests/phpunit9/` con `dnorte-core`. 4 pruebas nuevas
  para `HomeContentProvider`.

### Fixed

- `tools/build/package.sh`: `package_dnorte_theme()` copiaba una lista fija de
  archivos que nunca se actualizó al añadir plantillas nuevas — el `.zip` generado no
  incluía `front-page.php`/`single.php`/`archive.php`/`template-parts/`, y la portada
  real caía silenciosamente al `index.php` de fallback. Encontrado en la verificación
  visual, no por ningún test. Corregido copiando todos los `.php` de la raíz del tema
  automáticamente.
- `dnorte-theme`: faltaba `comments.php`, WordPress emitía un aviso de deprecación en
  cada artículo con comentarios abiertos. Mismo tipo de hallazgo ya documentado en el
  handoff de ND Platform.
- `dnorte-theme`: `archive.php` usaba `get_the_archive_title()` para el `<h1>` visible
  — WordPress core antepone "Category: "/"Tag: " (correcto para el `<title>` SEO, no
  para un encabezado visible). Mismo criterio ya aplicado en `BreadcrumbBuilder`
  (alpha.4).

### Verified

- `composer run check` (3 pruebas) y `composer test:integration` (4 pruebas) en verde
  en `dnorte-theme`; `dnorte-core` reverificado sin cambios (63 + 23).
- WordPress real de desarrollo con contenido real (9 artículos, 3 categorías,
  imágenes destacadas, autor asignado): portada con hero/última hora/cuadrícula,
  artículo con breadcrumbs en claro y oscuro, archivo de categoría con título limpio,
  `debug.log` vacío (incluido el aviso de `comments.php`, que ya no aparece).

## [Unreleased] — v0.1.0-alpha.6

### Added

- `dnorte-theme`: sistema de diseño ampliado (`app.scss`) — tokens de color
  (superficie, texto atenuado, enlaces visitados), tipografía (titulares serif del
  sistema, cuerpo sans del sistema, sin fuentes externas), escala de espaciado,
  radios/sombras.
- `dnorte-theme`: cabecera rediseñada — barra superior con fecha, masthead con línea
  de acento, botón visible para alternar modo oscuro, menú móvil con botón
  hamburguesa.
- `dnorte-theme`: pie de página con layout de columnas.
- `dnorte-theme`: `assets/js/app.js` — interacción del botón de tema y del menú
  móvil.

### Fixed

- `dnorte-theme`: `text-transform: capitalize` en la fecha de la barra superior
  capitalizaba cada palabra ("De August De"), rompiendo las reglas de capitalización
  del español. Encontrado en la propia verificación visual, no por revisión de
  código.
- `dnorte-theme`: `.header-controls` sin `flex-wrap` combinado con el `<nav>` en
  `flex-basis: 100%` comprimía el botón de menú móvil hasta volverlo invisible en
  pantallas angostas; el ícono SVG del botón tampoco tenía tamaño explícito.
  Corregido con `flex-wrap: wrap` + `flex-shrink: 0` + tamaño de ícono explícito.

### Verified

- `composer run check` (3 pruebas) en verde en `dnorte-theme`.
- WordPress real de desarrollo, escritorio y móvil (375px), claro y oscuro, con un
  menú real asignado: masthead, navegación, toggle de tema (confirmado disparando el
  evento `click` real del botón) y menú móvil funcionando, `debug.log` vacío.

## [Unreleased] — v0.1.0-alpha.5

### Added

- `dnorte-core`: `Media\ModernFormatConverter` — filtro nativo
  `image_editor_output_format`, formato preferido configurable (`config/media.php`),
  detección real de soporte GD, `avif` cae a `webp` si no hay soporte.
- `dnorte-core`: `Media\FeaturedImageSize` — tamaño `dnorte-featured` (1200×675,
  requisito de Discover), registrado en `after_setup_theme`.
- `dnorte-core`: `Providers\MediaServiceProvider`, registrado por defecto en
  `Application`. `Seo\Context\SeoContextResolver` actualizado para preferir
  `dnorte-featured` con fallback a `large`.
- `patchwork.json` — necesario para que Brain Monkey intercepte `function_exists()`
  en las pruebas de `ModernFormatConverter`.
- 8 pruebas unitarias nuevas (63 en total en `dnorte-core`).

### Verified

- `composer run check` (63 pruebas) y `composer test:integration` (23 pruebas) en
  verde.
- Imagen real subida vía WP-CLI sobre el WordPress de desarrollo: metadata del
  adjunto confirma tamaños intermedios generados en `.webp` (`mime-type:
  image/webp`), Biblioteca de medios renderizando sin errores, `debug.log` vacío.
  `dnorte-featured` correctamente omitido para una fuente menor a 1200×675
  (comportamiento nativo esperado de WordPress, no un bug).

## [Unreleased] — v0.1.0-alpha.4

### Added

- `dnorte-core`: `Seo\Context\SeoContext`/`SeoContextResolver` (única fuente de verdad
  SEO por página: singular, home, archivo, búsqueda, 404).
- `dnorte-core`: meta tags en `wp_head` — robots, canonical, OpenGraph, Twitter Cards
  (`Seo\Meta\*`).
- `dnorte-core`: Schema.org JSON-LD como un único `@graph` con `JSON_HEX_TAG|JSON_HEX_AMP`
  — `Organization`, `WebSite` (con `SearchAction`), `NewsArticle`, `BreadcrumbList`
  (`Seo\Schema\*`, `Seo\Breadcrumbs\BreadcrumbBuilder`).
- `dnorte-core`: `robots.txt` con directiva `Sitemap:` hacia el `wp-sitemap.xml`
  nativo de WordPress (`Seo\Robots\RobotsTxtBuilder`).
- `dnorte-core`: `Providers\SeoServiceProvider`, registrado por defecto en
  `Application`.
- 20 pruebas unitarias (Brain Monkey) para las piezas puras del módulo SEO; 9 pruebas
  de integración reales para `SeoContextResolver`, `BreadcrumbBuilder` y
  `ArticleSchema` (52 unitarias + 23 de integración en total).

### Fixed

- `dnorte-core`: `BreadcrumbBuilder` usaba `get_the_archive_title()` para el nombre de
  categoría/etiqueta en las migas de pan — WordPress core antepone "Category: "/
  "Tag: " a ese título (correcto para el `<title>` SEO, no para una miga de pan).
  Encontrado por la propia prueba de integración, no por revisión manual. Corregido
  usando `WP_Term::$name` directamente.

### Verified

- `composer run check` (52 pruebas) y `composer test:integration` (23 pruebas) en
  verde.
- `.zip` de `dnorte-core` regenerado e instalado sobre el WordPress real de
  desarrollo: meta tags, OpenGraph, Twitter Cards y JSON-LD (`Organization`+`WebSite`
  en portada; +`NewsArticle`+`BreadcrumbList` en un artículo real) verificados en el
  HTML servido; `robots.txt` con la directiva `Sitemap:`; `debug.log` vacío en todo
  el recorrido.

## [Unreleased] — v0.1.0-alpha.3

### Added

- Infraestructura de pruebas de integración con WordPress/MySQL reales:
  `tools/wp-tests/wordpress-develop/` (`git sparse-checkout` de `src` + `tests/phpunit`)
  y base de datos MariaDB local `dnorte_platform_test`.
- `tools/wp-tests/phpunit9/`: meta-proyecto Composer aislado (PHPUnit 9 +
  `yoast/phpunit-polyfills`) con autoloader propio (`DNorteCore\` → `dnorte-core/src/`
  por ruta) — el arnés de `wordpress-develop` todavía llama a un método interno
  eliminado en PHPUnit 10/11 (las que usan las pruebas unitarias).
- `dnorte-core`: `phpunit-integration.xml.dist`, `tests/Integration/bootstrap.php`
  (carga `dnorte-core.php` como mu-plugin vía `tests_add_filter('muplugins_loaded', ...)`,
  igual que en producción) y 14 pruebas de integración reales — `DatabaseManagerTest`,
  `MigratorTest`, `InstallerTest`, `SystemStatusControllerTest` (endpoint REST real de
  punta a punta, cierra el hueco documentado en la suite unitaria).
- `composer test:integration` (script nuevo en `dnorte-core/composer.json`).
- `tools/wp-tests/README.md`.

### Fixed

- `dnorte-core.php`: el autoload no comprobaba si las clases del plugin ya estaban
  cargadas por otro autoloader del mismo proceso — al requerir siempre su propio
  `vendor/autoload.php`, coexistían dos copias de PHPUnit (9 del arnés de integración,
  10 del propio paquete) en el mismo proceso, produciendo un fatal error. Corregido con
  un guard `class_exists('DNorteCore\Application')`, mismo patrón que `nd-core.php` en
  ND Platform.

### Verified

- `composer run check` (32 pruebas unitarias, 0 PHPCS, 0 PHPStan) y
  `composer test:integration` (14 pruebas contra WordPress/MySQL reales) en verde,
  ambos estables en corridas repetidas.
- `.zip` de `dnorte-core` regenerado e instalado sobre el WordPress real de desarrollo:
  front-end, `wp-json/dnorte/v1/system/status` y Plugins sin errores, `debug.log`
  vacío.


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
