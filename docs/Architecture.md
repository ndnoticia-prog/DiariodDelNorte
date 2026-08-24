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

**Migraciones y estado compartido, solo dentro de una misma invocación**:
`CoreServiceProvider::maybeRunUpgrade()` (enganchado a `init`) ejecuta
`Installer::install()` automáticamente en cada arranque — exactamente igual que en un
sitio real. La tabla `dnorte_migrations` ya existe para cuando cualquier clase de
prueba arranca *dentro de ese mismo proceso*; un test que la recree o la vacíe por
completo rompe esa invariante para el resto de la suite en esa invocación. Por eso
`MigratorTest` limpia solo su propia fila de fixture en `tearDown()`, nunca la tabla
completa.

**Corrección importante (encontrada en `v0.1.0-alpha.9`, no documentada
correctamente hasta ahora)**: esa persistencia **no** cruza invocaciones separadas de
`composer test:integration` — el paso "Installing..." que imprime el arnés de
`wordpress-develop` al arrancar recrea la base de datos de pruebas desde cero en cada
invocación del comando. Se confirmó consultando `dnorte_platform_test` directamente
con `mysql` entre dos corridas: las tablas `dnorte_*` no existían tras la primera.
Cualquier test que asuma estado dejado por una invocación *anterior* (proceso de
PHPUnit distinto) es frágil e informativamente engañoso, no solo lento; el patrón
correcto es que cada test que necesite comprobar una propiedad "entre invocaciones"
(p. ej. idempotencia de una migración) la reproduzca dentro de su propio método —
ver `MigrationRegistryTest`, que corre `Migrator::run()` dos veces en la misma
prueba en vez de asumir que una corrida previa ya dejó las tablas creadas.

**DDL y aislamiento transaccional**: `CREATE`/`DROP TABLE` producen un `COMMIT`
implícito en MySQL/MariaDB, rompiendo el aislamiento transaccional por-test de
`WP_UnitTestCase` para el resto de esa prueba. `DatabaseManagerTest` crea y elimina su
tabla de fixtures en `wpSetUpBeforeClass()`/`wpTearDownAfterClass()` (una sola vez para
toda la clase, fuera de cualquier transacción por-test), no dentro de un test
individual.

**FULLTEXT (InnoDB) y esa misma transacción, en la dirección contraria** (encontrado
en `v0.1.0-alpha.10`, mismo mecanismo que el punto anterior): InnoDB no hace
visibles las filas insertadas dentro de una transacción **sin confirmar** a una
búsqueda `MATCH ... AGAINST` contra un índice FULLTEXT — el motor solo fusiona esos
cambios al índice de texto completo en el `COMMIT`. Como `WP_UnitTestCase` envuelve
cada método de prueba en una transacción que nunca se confirma (solo hace
`ROLLBACK`, para aislar pruebas entre sí), un `WP_Post` creado con
`self::factory()->post->create()` **dentro** de un método de prueba de
`SearchQueryModifierTest`/`InternalSearchControllerTest` es invisible para
`SearchQueryModifier` — no por ningún bug del filtro, sino porque MySQL literalmente
no lo indexó todavía. Mismo arreglo que el caso de DDL: crear esos artículos de
fixture en `wpSetUpBeforeClass()`, que corre antes de que la transacción por-prueba
se abra.

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
  directiva `Sitemap:` a `robots.txt`.
- **Sitemap de Google News** (`Seo\Sitemap\NewsSitemapController`, sirviendo
  `/sitemap-news.xml`) — este sí es nuevo: WordPress core no lo provee, porque usa un
  espacio de nombres XML distinto (`news:`) y solo incluye artículos de las últimas
  horas (`config/seo.php`, `seo.news_sitemap.window_hours`, 48h por defecto — límite
  real del formato, no una preferencia editorial). `render()` construye el XML a
  partir de datos ya resueltos (`list<array{url, title, published_at}>`, nunca toca
  `WP_Post` directamente) con `XMLWriter` — así se cubre con pruebas unitarias
  (Brain Monkey) igual que `SchemaOutput`, sin necesitar WordPress real; solo
  `recentArticleData()` (la consulta a `WP_Query`) necesita integración. Límite de
  1000 URLs por sitemap, el máximo real que acepta Google News.
- **Rewrite rule del sitemap de noticias y activación**: como es una limitación
  conocida de WordPress (las reglas de rewrite añadidas dentro del propio hook de
  activación no llegan a tiempo para el `flush_rewrite_rules()` que corre en la misma
  petición, porque `init` —donde `SeoServiceProvider` registra la regla— ya se disparó
  antes de que se ejecute el hook de activación), si `/sitemap-news.xml` da 404 justo
  tras activar `dnorte-core`, basta con ir a Ajustes → Enlaces permanentes y pulsar
  "Guardar cambios" una vez. Mismo caso ya documentado en ND Platform para el mismo
  tipo de sitemap.
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
- **Por qué `SeoContextResolver`/`BreadcrumbBuilder`/`ArticleSchema`/
  `NewsSitemapController::recentArticleData()` sí tienen pruebas de integración (y no
  solo unitarias)**: dependen de `WP_Post`/`WP_Term`/`WP_Query` reales
  (`get_queried_object()`, `get_the_category()`, ...) — misma limitación que
  `DatabaseManager`/`Migrator`/`Installer` (ver más arriba), cubierta ahora que la
  infraestructura de integración ya existe desde `v0.1.0-alpha.3`. Los constructores
  puros (`RobotsMetaBuilder`, `OpenGraphBuilder`, `TwitterCardBuilder`,
  `MetaTagsRenderer`, `OrganizationSchema`, `WebSiteSchema`, `BreadcrumbListSchema`,
  `SchemaOutput`, `RobotsTxtBuilder`, `NewsSitemapController::render()`) sí se cubren
  con pruebas unitarias (Brain Monkey), porque solo reciben datos ya resueltos (un
  `SeoContext` o una lista de ítems), nunca tocan `WP_Post` directamente.

## Multimedia: qué reimplementa `dnorte-core` y qué reutiliza de WordPress core

- **Responsive images (`srcset`/`sizes`) y lazy load**: no se reimplementan.
  WordPress core ya los genera automáticamente desde la 4.4 y la 5.5
  respectivamente.
- **WebP/AVIF sí es nuevo**: `Media\ModernFormatConverter` usa el filtro nativo
  `image_editor_output_format` (WordPress 5.8+) para que los tamaños intermedios de
  JPEG/PNG subidos se generen en un formato moderno, comprobando en tiempo real si el
  GD del servidor soporta `imagewebp()`/`imageavif()` antes de activarlo — degrada a no
  convertir si no hay soporte, nunca fuerza un formato no disponible. El formato
  preferido (`webp`/`avif`/desactivado) es configurable (`config/media.php`,
  `media.modern_format`); `avif` cae a `webp` automáticamente si el servidor no
  soporta AVIF.
- **Tamaño de imagen destacada** (`Media\FeaturedImageSize`, `dnorte-featured`,
  1200×675): registrado en `after_setup_theme`, cumple el requisito de Google Discover
  (imagen ≥1200px de ancho). `Seo\Context\SeoContextResolver` referencia ese nombre de
  tamaño como **cadena literal**, no como dependencia de clase — mismo patrón de bajo
  acoplamiento que `nd-seo`/`nd-discover` en ND Platform. Si el post no tiene una
  versión de ese tamaño (fuente más pequeña que 1200×675, WordPress no la genera para
  evitar ampliar-y-recortar con mala calidad), `get_the_post_thumbnail_url()` devuelve
  `false` y se cae explícitamente a `large`, nunca a un fatal error ni a una imagen
  rota.

## Menú de administración

Mismo patrón que providers/REST (ver "Extender el registro de providers" más arriba):
filtro público `dnorte_core/admin_pages` + contrato `Admin\Contracts\RegistersAdminPages`
+ value object `Admin\AdminPage`. `Providers\AdminMenuServiceProvider` (enganchado a
`admin_menu`) resuelve cada clase del filtro vía el `Container`, junta todos los
`AdminPage` devueltos y los ordena por `position`. Un módulo nuevo se suma sin que
`dnorte-core` tenga que conocer su existencia, igual que `dnorte_core/rest_controllers`.

`AdminPage->render` se tipa como `Closure`, no `callable`: `callable` no es un tipo de
propiedad legal en PHP (`Readonly property must have type` es un error fatal, no un
aviso), así que el constructor convierte el `callable` recibido con
`Closure::fromCallable()`.

**`AdminPage->parentSlug` decide el nivel superior — corregido en v0.1.0-alpha.11**:
la versión original (única hasta esa versión, cuando solo existía el módulo de
Turnos) elegía la página de **menor `position` de toda la plataforma** como la
única entrada de nivel superior y anidaba cualquier otra página nueva debajo de
esa, sin importar de qué módulo viniera — invisible mientras solo había un único
`RegistersAdminPages` registrado, pero habría anidado "Analítica" bajo "Turnos"
sin ninguna relación real entre ambos en cuanto se sumó el segundo módulo con
panel propio. Corregido dándole a cada `AdminPage` su propio `parentSlug` explícito:
`null` (por defecto) la vuelve su propia entrada de nivel superior
(`add_menu_page()`); un slug la anida como `add_submenu_page()` bajo esa página
concreta — nunca inferido por posición. `position` se conserva solo para el orden
relativo entre páginas de nivel superior o entre submenús que comparten el mismo
`parentSlug`, no para decidir cuál se vuelve el nivel superior. Ver
`AdminMenuServiceProviderTest::test_register_menu_gives_two_unrelated_modules_their_own_top_level_entry`
(prueba de regresión) y "Fixed" en `CHANGELOG.md`.

```php
add_filter('dnorte_core/admin_pages', function (array $pages): array {
    $pages[] = MiModulo\Admin\MiPaginaAdmin::class;

    return $pages;
});
```

## Workflow editorial

`Workflow\Status\EditorialStatusRegistrar` añade dos estados de post propios
(`dnorte_in_review`/`dnorte_needs_changes`, `public: false`, `internal: true`,
`protected: true` — no aparecen en el front-end ni en los listados públicos).
`Workflow\Notes\EditorialNoteRepository` (tabla `dnorte_editorial_notes`) guarda notas
internas por artículo. `Workflow\Assignments\ArticleAssignmentRepository` asigna un
artículo a un periodista vía postmeta (`_dnorte_assigned_to`) — deliberadamente
postmeta y no una tabla propia, porque es una relación 1-a-1 con el post que ya
encaja en el modelo nativo de WordPress, sin necesitar índices ni consultas propias
más allá de lo que `WP_Query`/`get_post_meta()` ya resuelven.

`Workflow\Shifts\ShiftRepository` (tabla `dnorte_shifts`: periodista, rol, inicio,
fin, notas) sí es una tabla propia — a diferencia de una asignación de artículo, un
turno no cuelga de ningún post concreto. `Shift->isActiveAt(DateTimeImmutable $moment)`
centraliza la comparación de rango de fechas para que "en turno ahora" y "próximo
turno" usen exactamente la misma lógica.

**`Workflow\Shifts\ShiftsAdminPage`** es el panel "Turnos" — el panel de asignación de
roles para los periodistas de turno pedido explícitamente para Diario del Norte, capa
`edit_others_posts`. Registra el CRUD completo (crear/eliminar turno) vía
`dnorte_core/admin_pages`. El manejo de `$_POST`/`$_GET` para crear/eliminar vive en
métodos separados (`handleCreate()`/`handleDelete()`) de donde se verifica el
nonce (`handleRequest()`), por lo que WPCS marca falsos positivos de
`NonceVerification.Missing`/`Recommended` en esos métodos — silenciados con
`phpcs:ignore` inline documentando la verificación cruzada, mismo criterio que ya
usa `nd-core` en ND Platform para el mismo patrón.

Los roles de turno disponibles (`editor_en_turno`/`redactor_en_turno`/
`community_manager`) están en `config/workflow.php`, no hardcodeados en
`ShiftsAdminPage` — un módulo o el propio sitio puede ampliar la lista sin tocar
código.

## `Installer\MigrationRegistry` y el orden de arranque en la activación

`register_activation_hook()` se ejecuta en la carga del archivo principal del plugin,
**antes** de que `Application::boot()` corra (que depende de `after_setup_theme`, y
por tanto de que el tema ya haya cargado) — el `Container` y los `ServiceProvider` no
existen todavía en ese momento, así que el activation hook no puede resolver
migraciones registradas dinámicamente por cada módulo vía filtro, como sí se hace con
providers/rutas/páginas de admin. `Installer\MigrationRegistry::all()` resuelve esto
con una lista estática simple, sin contenedor, usada tanto por el activation hook en
`dnorte-core.php` como por `CoreServiceProvider::maybeRunUpgrade()` — un único lugar
que enumera todas las migraciones del plugin.

**Disciplina obligatoria, con un incidente real detrás (ver "Fixed" en
`CHANGELOG.md` de `v0.1.0-alpha.9`)**: `CoreServiceProvider::maybeRunUpgrade()` solo
vuelve a correr `Installer::install()` cuando `DNORTE_CORE_VERSION` (la constante en
`dnorte-core.php`) difiere de la versión guardada en `wp_options`. Añadir una
migración a `MigrationRegistry::all()` sin subir esa constante la deja sin efecto en
cualquier sitio que ya tuviera el plugin instalado — exactamente lo que pasó entre
`v0.1.0-alpha.2` y `v0.1.0-alpha.8`, sin que ningún test lo detectara porque los
tests de integración siempre arrancan contra una base de datos nueva. Toda migración
nueva exige subir `DNORTE_CORE_VERSION` (y la cabecera `Version:`) en el mismo
commit.

## Búsqueda interna: qué reimplementa `dnorte-core` y qué reutiliza de WordPress core

- **Paginación, `post_status`, permisos, caché de objeto**: no se reimplementan.
  Tanto `search.php` (dnorte-theme) como `InternalSearchController` arman un
  `WP_Query` normal con `s` — todo lo que `WP_Query`/`the_posts_pagination()` ya
  resuelven se reutiliza tal cual.
- **Lo único nuevo es el ranking por relevancia**: WordPress core busca con
  `LIKE '%término%'` sobre `post_title`/`post_content` y ordena por fecha — sin
  ninguna noción de "qué tan bien encaja" un resultado. `Search\SearchQueryModifier`
  sustituye eso enganchándose a los filtros nativos `posts_search`/`posts_orderby`
  (aplicándose a **cualquier** `WP_Query` con `is_search()` verdadero, no solo a la
  consulta principal de la página de resultados) por un `MATCH (post_title,
  post_content) AGAINST (... IN BOOLEAN MODE)` sobre el índice FULLTEXT que crea
  `Search\Fulltext\CreateSearchFulltextIndex` — la única migración de la plataforma
  que altera una tabla nativa de WordPress (`wp_posts`) en vez de crear una tabla
  `dnorte_*` propia (ver el docblock de esa clase).
- **`Search\BooleanModeTermBuilder`** traduce el término del visitante a sintaxis
  booleana de MySQL (limpia operadores reservados, añade `*` a cada palabra para
  que funcione como "empieza por" — pensado para una caja de sugerencias en vivo,
  no solo la página de resultados). Es una pieza pura, sin dependencia de
  WordPress, separada a propósito de `SearchQueryModifier` para poder probarla con
  Brain Monkey sin necesitar un `WP_Query` real.
- **`DatabaseManager::fragment()`**: excepción documentada al principio de "único
  punto de acceso a `$wpdb`" — expone `wpdb::prepare()` como un trozo de SQL ya
  escapado sin ejecutar ninguna consulta, porque `posts_search`/`posts_orderby`
  esperan de vuelta exactamente eso (un fragmento para que `WP_Query` lo inserte en
  la consulta que arma él mismo), no un resultado.
- **`GET /wp-json/dnorte/v1/search?q=...`** (`InternalSearchController`): endpoint
  ligero para una caja de búsqueda con sugerencias en vivo — reutiliza el mismo
  `WP_Query` con `s`, así que hereda el ranking por relevancia sin duplicar esa
  lógica. Términos más cortos que `search.min_query_length` (`config/search.php`)
  se responden con una lista vacía sin tocar la base de datos.

## Analítica propia: qué mide, qué NO mide, y por qué

Pensada para responder "¿qué se lee?", no "¿quién lee?" — una decisión de diseño
deliberada, no una limitación técnica temporal:

- **`dnorte_pageviews`** (`Analytics\Pageviews\CreatePageviewsTable`) guarda
  únicamente `post_id`, `referrer_host` (solo el dominio del referente — nunca la
  URL completa con parámetros de terceros, ver `PageviewController::extractHost()`)
  y `viewed_at`. Sin IP, sin user-agent, sin ninguna cookie ni identificador de
  visitante — no hay forma de reconstruir el recorrido de una persona concreta a
  partir de esta tabla. Es la razón por la que "visitantes únicos" no existe en el
  panel v1: medirlo de forma honesta exige algún tipo de identificador
  (aunque sea un hash rotativo), y no se justificaba para la primera versión.
- **`Analytics\PageviewBeaconRenderer`** (enganchado a `wp_footer`, sin tocar el
  tema) emite un `<script>` mínimo con `navigator.sendBeacon()` — deliberadamente
  un beacon del navegador, no un `INSERT` directo en el `render()` de PHP: así una
  vista servida desde una caché de página (si se añade en el futuro) se sigue
  contando, porque la ejecuta el navegador de cada visitante, no el servidor en el
  momento de generar el HTML.
- **Excluye al propio equipo editorial**: `PageviewBeaconRenderer::shouldRecord()`
  no emite el beacon si `current_user_can('edit_posts')` — sin esto, cada
  vista previa o revisión de un artículo por el equipo contaminaría las
  estadísticas de lectura real.
- **Sin detección de bots/crawlers** (limitación de v1, no resuelta): un rastreador
  que ejecute JavaScript (poco común, pero existe) se contaría igual que una
  persona real. Aceptable para una primera versión; revisar si el volumen de
  tráfico automatizado resulta significativo.
- **`Analytics\PageviewPurger`**: WP-Cron diario (`dnorte_core/analytics_purge`,
  programado en `init` si no existe ya, ver `AnalyticsServiceProvider::schedulePurge()`)
  borra filas más antiguas que `analytics.retention_days` (90 por defecto) — no es
  un requisito de privacidad estricto (no hay datos personales que purgar), es
  higiene de tamaño de tabla.
- **`Analytics\AnalyticsAdminPage`**: panel de solo lectura (sin ningún formulario
  que verificar con nonce, a diferencia del panel de turnos) — vistas totales
  24h/7d/30d y los artículos más vistos en `analytics.top_articles_window_days`
  (7 días por defecto).
- **`GET /wp-json/dnorte/v1/search`** e internal search comparten el mismo criterio
  de "reutilizar `WP_Query` en vez de reinventar consultas": aquí, en cambio, sí
  hace falta una tabla y consultas propias (`PageviewRepository`), porque
  WordPress core no tiene ningún concepto nativo de "cuántas veces se vio este
  post".

## Publicidad propia: campañas, no un anuncio fijo por espacio

Los cinco espacios pedidos explícitamente para Diario del Norte
(`config/ads.php`: `cabecera`, `inicio`, `top_noticia`, `intermedio`, `final`)
son los mismos desde `v0.1.0-alpha.12`, pero el modelo de datos cambió por
completo en `v0.1.0-alpha.13` a partir del formulario real de campañas del
cliente: una **campaña** (`Ads\Campaign`) ya no vive atada a un único espacio —
puede dirigirse a varios a la vez, con prioridad y segmentación opcional por
categoría, y soporta Google AdSense de forma nativa además del HTML/banner
propio del v1 original.

- **Dónde caen los cinco espacios (sin cambios desde alpha.12)**: Cabecera/Inicio
  son sitewide, fuera del flujo de contenido — dos hooks propios y mínimos que
  `dnorte-theme` añade en `header.php` (`dnorte_theme/before_topbar`/
  `after_header`), la única lógica que el tema tuvo que sumar para todo el
  módulo. Top noticia/Intermedio/Final viven enteramente en `dnorte-core`, sin
  tocar ninguna plantilla, vía el filtro `the_content` en prioridad 20 (después
  de `wpautop`, para que `Ads\ContentParagraphInjector` opere sobre HTML con
  `<p>` reales). `Ads\ContentParagraphInjector` sigue siendo una pieza pura.
- **`dnorte_ad_campaigns`** (`Ads\Migrations\CreateAdCampaignsTable`) reemplaza
  a `dnorte_ads` (`v0.1.0-alpha.12`, ahora eliminada por
  `Ads\Migrations\DropLegacyAdsTable` — ver el docblock de esa migración sobre
  por qué es una migración nueva, no una reescritura). `zones`/`categories` son
  listas separadas por comas en una sola columna, no una tabla de unión aparte:
  con cinco espacios fijos y un puñado de campañas reales, filtrar en PHP
  (`Campaign::appliesToZone()`/`appliesToCategories()`) resuelve lo mismo que un
  `JOIN` sin el coste de mantener una tabla más.
- **`CampaignRepository::forZone()`** resuelve, para un espacio y un momento
  dados, la campaña activa (`enabled` + dentro de `starts_at`/`ends_at`) que se
  dirige a ese espacio y a las categorías del contenido actual, con más
  `priority` — filtrando y ordenando en PHP sobre `all()`, no con una consulta
  SQL a medida por cada combinación posible de filtros.
- **Segmentación por categoría, incluida su interacción con Cabecera/Inicio**:
  una campaña sin categorías configuradas se dirige a todas, incluidos los
  espacios sitewide (que no tienen ningún artículo de contexto,
  `AdsServiceProvider` les pasa una lista vacía). Una campaña CON categorías
  configuradas nunca aparece en Cabecera/Inicio — la intersección con una lista
  vacía siempre es vacía, y "solo en Deportes" no tiene sentido fuera de un
  artículo de Deportes.
- **Tipo `adsense`**: `Ads\AdSlotRenderer` imprime solo el `<ins class="adsbygoogle">`
  de cada unidad — el `<script>` que carga `adsbygoogle.js` se encola una única
  vez por página con `wp_enqueue_script()` (`AdsServiceProvider::enqueueAdSenseLoader()`,
  enganchado a `wp_enqueue_scripts`, no a `wp_head` directamente — hookear tan
  tarde como `wp_head` habría llegado después de que `wp_print_head_scripts()`
  (prioridad 9 de ese mismo hook) ya imprimiera la cola). Usa el Client ID de la
  primera campaña AdSense activa que encuentra — simplificación correcta para
  una sola cuenta de AdSense (el caso real), documentada como tal en el
  docblock del método.
- **`Ads\AdSlotRenderer`** imprime el marcado (HTML propio o la unidad AdSense)
  **sin escapar** — a propósito: es contenido de terceros por diseño. Por eso
  `Ads\AdsAdminPage` exige `manage_options` (más estricto que
  `edit_others_posts` en Turnos/Analítica) — la capacidad de inyectar HTML/JS en
  todo el sitio queda reservada a quien administra el sitio, mismo nivel de
  confianza que WordPress ya da a `unfiltered_html`.
- **Panel "Publicidad"**: una tabla de campañas existentes + un único
  formulario para crear una nueva o editar una vía `?edit={id}` — no un
  formulario repetido por espacio (el diseño de alpha.12). `CampaignRepository::save()`
  crea o reemplaza según si el `Campaign` que recibe trae `id > 0`, evitando
  duplicar `create()`/`update()` para el único llamador real.
- **Etiqueta "Publicidad"** (`app.scss`, `.dnorte-ad::before`): transparencia
  hacia el visitante, criterio estándar de la industria — no es solo estética.

### Estadísticas, evidencia, informes e historial (v0.1.0-alpha.14)

Ampliación pedida a partir del panel de campañas real del cliente (columnas
ESTADÍSTICAS/ACCIONES con Desactivar/Subir evidencia/Generar informe/Borrar, y
una pestaña Historial):

- **Impresiones/clics**: `Ads\CampaignEventController`
  (`POST /wp-json/dnorte/v1/ads/impression`/`.../click`) recibe el beacon que
  emite `AdsServiceProvider::renderTrackingScript()` — un único script compartido
  por página (`wp_footer`), con `data-campaign-id` puesto por `AdSlotRenderer` en
  cada `.dnorte-ad` y delegación de eventos (`document.addEventListener('click', ...)`)
  en vez de un listener por anuncio. Excluye al equipo editorial
  (`current_user_can('edit_posts')`), mismo criterio que
  `Analytics\PageviewBeaconRenderer`, para que "Generar informe" no muestre las
  propias vistas previas del equipo como si fueran lectores reales.
  `CampaignRepository::recordImpression()`/`recordClick()` incrementan con
  `UPDATE ... SET x = x + 1` (atómico), no lectura-modificación-escritura.
  **Limitación deliberada** (mismo criterio que el resto de la analítica propia
  de la plataforma): sin deduplicación ni detección de bots — un recuento
  aproximado para uso propio, no una cifra verificada para facturar al
  anunciante. Los clics dentro de una unidad de AdSense los cuenta y factura
  Google, no dnorte-core.
- **`Campaign::ctr()`**: porcentaje de clics por impresión (0 sin impresiones,
  evita dividir entre cero) — el mismo dato que muestra la columna
  "Estadísticas" ("767 impr. · 1 clics · 0.13% CTR").
- **Evidencia**: `AdRepository::addEvidence()` (ahora `CampaignRepository`) añade
  un id de adjunto de la Biblioteca de medios a `evidence_ids` (lista separada
  por comas, mismo criterio que `zones`/`categories`) — sube el archivo con
  `media_handle_upload()` nativo de WordPress en vez de una implementación
  propia de subida de ficheros.
- **"Generar informe"**: página imprimible dentro del propio panel (no un PDF
  generado en servidor — evita sumar una dependencia nueva solo para esto). El
  `@media print` de `AdsAdminPage::renderStyles()` oculta el menú/barra de
  administración de WordPress por sus ids nativos (`#adminmenumain`,
  `#wpadminbar`, ...) al imprimir/"Guardar como PDF" desde el navegador —
  patrón habitual en plugins de WordPress para "vistas imprimibles" sin salir
  de wp-admin.
- **Historial** (`dnorte_ad_campaign_history`, `Ads\CampaignHistoryRepository`):
  una fila por acción de escritura (creada/actualizada/activada/desactivada/
  borrada/evidencia subida), con quién y cuándo. `campaign_name` se guarda como
  copia, no solo `campaign_id`, para que el historial de una campaña ya borrada
  siga siendo legible sin depender de un `JOIN` a una fila que ya no existe.
- **Bug real encontrado en la verificación del navegador, no por ningún test**:
  los enlaces "Nueva campaña"/pestañas construidos con `remove_query_arg()`
  seguían arrastrando `dnorte_ads_action`/`id`/`_wpnonce` de la URL actual justo
  después de ejecutar un "Activar"/"Desactivar"/"Borrar" — un clic posterior en
  cualquiera de ellos volvía a ejecutar esa misma acción (con el mismo nonce,
  todavía válido) en vez de solo navegar. Corregido centralizando todos esos
  enlaces en `AdsAdminPage::cleanBaseUrl()`, que retira también los tres
  parámetros de acción, no solo los de vista (`tab`/`edit`/`evidence`/`report`).

### Seis tipos de campaña, con el formulario reducido al tipo elegido (v0.1.0-alpha.15)

Ampliación pedida a partir de la lista real de tipos del cliente
(adsense/gam/html/image/video/sponsored, en ese orden en el `<select>`):

- **Tipos nuevos**: `gam` (Google Ad Manager), `video` (banner de vídeo propio,
  autoreproducido/silenciado/en bucle — no un anuncio de pre-roll con VAST/VMAP,
  fuera de alcance), `sponsored` (contenido patrocinado: imagen + texto
  descriptivo corto, pensado como formato nativo, no un simple banner).
  `Campaign` gana `gamAdUnitPath`/`gamSizes`/`videoUrl`/`description` — todos al
  final del constructor (compatibilidad con las llamadas posicionales
  existentes) y con default `''`, así que cualquier campaña de un tipo que no
  los usa simplemente los deja vacíos.
- **Google Ad Manager**: mismo patrón que AdSense — `AdSlotRenderer` solo
  define/muestra la unidad concreta (`googletag.defineSlot()` con la ruta y los
  tamaños de la campaña, parseados de `"728x90,970x250"` a `[[728,90],[970,250]]`
  por `AdSlotRenderer::parseGamSizes()`, que descarta un par mal escrito en vez
  de romper la página); `gpt.js` (la librería de GAM) se encola una única vez
  por página en `AdsServiceProvider::enqueueGamLoader()`
  (`wp_enqueue_scripts`, igual que `enqueueAdSenseLoader()`), solo si hay
  alguna campaña GAM activa. Mismo límite que AdSense: los clics/impresiones
  reales de una unidad GAM los mide y los factura Google, no dnorte-core.
- **Formulario reducido al tipo elegido**: antes se mostraban todos los campos
  de todos los tipos a la vez, con una nota "se ignora si el tipo es...". Ahora
  cada fila del formulario (`renderTextRow()`/`renderHtmlRow()`) puede llevar
  un atributo `data-ad-fields-for="tipo1,tipo2"`, y un único `<script>` por
  formulario (`AdsAdminPage::renderTypeToggleScript()`) oculta las filas que no
  corresponden al `<select>` de Tipo actual, tanto al cargar la página (para
  que "Editar campaña" ya arranque mostrando los campos correctos) como en cada
  cambio de selección. Primer JS en un panel de administración de esta
  plataforma (Turnos/Analítica no necesitan ninguno) — deliberadamente
  vainilla, sin jQuery ni ninguna dependencia nueva.

### PDF real del informe, con logo y evidencia embebidos (v0.1.0-alpha.16)

"Generar informe" tenía, desde `v0.1.0-alpha.14`, una vista imprimible vía
`window.print()` del navegador (sin generación en servidor, documentado como
simplificación deliberada). El cliente pidió explícitamente un PDF real
descargable, con el logo de Diario del Norte y la foto de evidencia
incrustada (no solo enlazada) — esta versión lo reemplaza:

- **`dompdf/dompdf` (^3.1)**: primera dependencia de producción real de toda
  la plataforma (hasta ahora `dnorte-core/composer.json`'s `require` solo
  pedía PHP). Elegido sobre mPDF/TCPDF/wkhtmltopdf por ser PHP puro sin
  binario de sistema — el único tipo de opción viable en hosting compartido
  de WordPress.
- **`Ads\CampaignReportPdfRenderer`**: arma un HTML propio (mismo contenido
  que `AdsAdminPage::renderReportView()`, pero standalone: sin el chrome de
  wp-admin) y lo convierte a bytes de PDF vía dompdf. El logo
  (`dnorte-core/assets/images/dnorte-logo.png`, bundleado con el plugin) y
  cada foto de evidencia se embeben como `data:` URI en base64 directamente
  en el HTML — dompdf corre en el servidor sin sesión de navegador ni
  cookies, así que una `<img src="https://...">` normal le obligaría a
  volver a descargar cada imagen por HTTP; por eso también
  `Options::setIsRemoteEnabled(false)`, para que cualquier URL remota que se
  colara se ignore en vez de intentar descargarse. La evidencia usa la
  variante "large" (máx. 1024px) generada por WordPress al subir la imagen,
  no el archivo original — una foto de evidencia subida desde un móvil (varios
  MB) no gana nada visualmente a este tamaño de impresión y solo infla el PDF.
  Evidencia que no es imagen (ej. un PDF de contrato) cae a un enlace de
  texto, igual que en la vista en pantalla.
- **Descarga vía `admin_init`, no dentro de `AdsAdminPage::render()`**: para
  cuando `render()` corre, WordPress ya empezó a imprimir el HTML del panel
  — enviar cabeceras `Content-Type: application/pdf` en ese punto ya es
  tarde. `AdsServiceProvider::maybeDownloadReportPdf()` engancha
  `admin_init` (corre antes de cualquier salida de la página), detecta
  `?page=dnorte-publicidad&pdf={id}`, verifica `manage_options`, genera el
  PDF y termina la petición con `header()` + `echo` + `exit`.
- **`AdsAdminPage::TYPES` pasó de `private` a `public const`** (con un nuevo
  helper estático `AdsAdminPage::typeLabel()`) para que
  `AdsServiceProvider::maybeDownloadReportPdf()` arme la etiqueta del tipo de
  campaña sin duplicar el mapa.
- **Vista en pantalla también actualizada**: tanto "Generar informe" como
  "Subir evidencia" ahora muestran la foto de evidencia como miniatura
  embebida (`AdsAdminPage::renderEvidenceThumbnail()`), no solo como enlace de
  texto — mismo criterio que usa el PDF, para que pantalla y PDF descargado
  coincidan.
- **Empaquetado**: `tools/build/package.sh` ahora corre
  `composer install --no-dev --optimize-autoloader` dentro de la copia en
  stage antes de zipear `dnorte-core` (primera vez que el zip del plugin
  necesita un `vendor/` — antes no hacía falta ningún paso de Composer).
  `composer.json`/`.lock` se copian al stage solo para esa instalación y se
  borran después; el zip final nunca los incluye.
- **Sin test unitario**: todo lo que hace `CampaignReportPdfRenderer` (leer un
  adjunto real de la Biblioteca de medios, generar bytes de PDF con una
  librería real) requiere WordPress y un archivo real en disco — se prueba a
  nivel de integración (`tests/Integration/Ads/CampaignReportPdfRendererTest.php`,
  que solo comprueba la firma `%PDF-` y el tamaño de los bytes devueltos, no
  el contenido visual — eso se verificó a mano en el navegador durante esta
  misma versión).
- **`tools/wp-tests/phpunit9/composer.json` ganó un mapa de autoload manual
  para dompdf y su árbol de dependencias** (`Dompdf\`, `FontLib\`, `Svg\`,
  `Masterminds\`, `Sabberworm\CSS\`, más el classmap/files de
  `thecodingmachine/safe`), apuntando directo a rutas dentro de
  `dnorte-core/vendor/` — el mismo patrón ya usado para `DNorteCore\`/
  `DNorteTheme\`. Motivo: este arnés de PHPUnit 9 nunca carga el
  `vendor/autoload.php` completo de ningún paquete (arrastraría el
  `phpunit/phpunit` ^10.5 de `require-dev` de ese paquete y su archivo global
  `Functions.php` con `assertEquals()`/`assertTrue()`/etc., que chocaría con
  las funciones globales que ya declara este PHPUnit 9) — así que cualquier
  dependencia de producción nueva que un paquete necesite en sus pruebas de
  integración debe mapearse aquí a mano, igual que se hizo con dompdf. El
  orden del array `files` importa: `thecodingmachine/safe` (define las
  funciones `Safe\*`) debe listarse antes que `sabberworm/php-css-parser`
  (las llama al cargar `Rule.php`, no dentro de una función) — invertirlo
  revienta con `Call to undefined function Safe\class_alias()`. Tras editar
  ese archivo hay que correr `composer dump-autoload` dentro de
  `tools/wp-tests/phpunit9/`.

## Portada real de `dnorte-theme` (v0.1.0-alpha.17)

Rediseño completo de `front-page.php` a partir de una maqueta real del
cliente: cabecera con logo/ubicación/buscador colapsable, barra de categorías
con desplegable "Más", grupo "Lo último" (hero + tira de miniaturas + columna
de tarjetas), bloques de categoría (La Guajira, Judiciales), Opinión,
Editorial + Edición Impresa, Lo más leído y "Más noticias" con "Cargar más".

- **Tipografía autoalojada**: `Playfair Display` (titulares) + `Source Sans 3`
  (cuerpo/UI), ver `assets/fonts/README.md`. Reemplaza las pilas de fuentes
  del sistema que el tema usaba hasta ahora — el motivo para no cargarlas
  desde Google Fonts sigue siendo el mismo (evitar una petición a un
  tercero en cada visita), autoalojar simplemente resuelve la fidelidad
  visual sin reabrir esa concesión.
  - **`vite.config.js` necesita `base: './'`**: por defecto Vite genera
    `url(/assets/foo.woff2)` (absoluta desde la raíz del dominio) para
    cualquier asset referenciado en CSS — correcto para una SPA servida
    desde la raíz, roto para un tema de WordPress, que vive en
    `/wp-content/themes/dnorte-theme/`. Bug real encontrado en la
    verificación en el navegador (`read_network_requests` mostrando 404 en
    `/assets/*.woff2` en vez de `/wp-content/themes/dnorte-theme/dist/
    assets/*.woff2`) — sin este ajuste, las fuentes fallan en silencio (sin
    ningún error de PHP) y el navegador cae a la pila de respaldo.
- **`Content\DefaultContentSeeder`**: siembra, una única vez por sitio, las 19
  categorías de la maqueta (10 de primer nivel + las 9 de "Más") y un menú
  `primary` con esa misma estructura — para que un sitio recién desplegado
  tenga dónde enlazar y qué mostrar en cada bloque sin que el cliente arme
  21 elementos de menú a mano. Enganchado a `after_setup_theme` (corre en
  cada carga) con una opción (`dnorte_theme_default_content_seeded`) como
  guarda de "ya sembrado" — **nunca a `after_switch_theme`**, que solo
  dispara al cambiar de tema activo, no al actualizar la versión de un tema
  que ya estaba activo (el caso real de desplegar esta versión sobre un
  sitio que ya tiene `dnorte-theme` puesto). Nunca pisa una categoría que ya
  exista (busca por slug) ni un menú ya asignado a `primary` — si el equipo
  editorial ya armó el suyo, esta clase no lo vuelve a tocar jamás.
- **`Content\HomeContentProvider`** reescrita por completo: una `WP_Query`
  por sección (hero/miniaturas, columna aparte, por categoría, "Más
  noticias") en vez de la única consulta repartida por posición que usaba la
  versión anterior — cada sección tiene un origen distinto (categoría
  concreta, o ranking por vistas en vez de fecha) que no cabe en una sola
  consulta. Deduplicación deliberadamente parcial: "Más noticias" excluye
  los posts ya mostrados en "Lo último" (mismo fondo común de "más
  recientes", ahí el duplicado se vería como un error de plantilla) pero los
  bloques de categoría NO se excluyen entre sí ni contra el hero — un
  artículo puede ser a la vez la noticia más comentada del momento y
  pertenecer a La Guajira; eso es real en cualquier portada de diario, no un
  bug.
- **"Lo más leído" reutiliza `Analytics\Pageviews\PageviewRepository::
  topArticlesSince()`** (ya existente desde la analítica propia) resuelto vía
  `Application::instance()->container()->get(...)` — con `try/catch` porque
  ese contenedor no siempre está arrancado (ej. las pruebas de integración de
  `dnorte-theme` no cargan `dnorte-core.php`, ver `tests/Integration/
  bootstrap.php`; o un sitio real con Analítica desactivada) — si falla o no
  hay suficientes vistas todavía, completa con los artículos más recientes en
  su lugar, nunca deja la sección vacía ni rota por falta de datos
  históricos.
- **"Edición impresa" reutiliza el mismo patrón de categoría** que el resto
  de bloques (el último post de la categoría `edicion-impresa`, su imagen
  destacada hace de "portada" del día) — deliberadamente, para no construir
  un sistema nuevo de subida de PDF/portada en esta pasada.
- **Banner de WhatsApp**: no es ninguno de los cinco espacios de publicidad
  (`Ads\AdSlotRenderer`) — es un ajuste propio del tema
  (`ThemeServiceProvider::registerCustomizer()`, panel "Diario del Norte" en
  el Personalizador: ciudad de la barra superior, número de WhatsApp, redes
  sociales). Sin número configurado, el banner no se imprime — nunca un
  número de ejemplo simulando ser real.
- **"Cargar más"** usa la API REST nativa de WordPress (`wp/v2/posts`, con
  `_embed=1` para traer imagen destacada y categoría) desde
  `assets/js/app.js` — sin endpoint propio nuevo. Degrada sin JS: el resto de
  la portada funciona igual, el botón simplemente no responde.
- **Color por categoría**: mapa cerrado `$category-colors` en `app.scss`,
  aplicado vía `data-category="{slug}"` en el propio `<a class="kicker">` —
  ninguna plantilla PHP conoce la paleta, solo pasa el slug real de
  WordPress; una categoría sin entrada en el mapa cae al acento de marca.
- **`custom-logo`**: el tema no lo declaraba (`add_theme_support`) hasta
  ahora, así que el Personalizador nunca mostraba la opción de subir un
  logo — corregido. Respaldo si no hay uno subido:
  `assets/images/dnorte-logo.png` (copia propia del logo real, independiente
  del ejemplar que usa `dnorte-core` para el PDF de "Generar informe" — cada
  paquete es autónomo, ninguno depende de la estructura interna de
  `assets/` del otro).

## Evolución editorial de la portada (v0.1.0-alpha.18)

A partir de un segundo prompt del cliente ("periódico digital regional
premium", no una plantilla genérica), evoluciona la portada de alpha.17 sin
rehacerla desde cero — mismo `Content\HomeContentProvider`/sistema de
bloques, cambia la composición y se suman dos módulos nuevos (newsletter,
fecha relativa).

- **Hero de foto completa**: reemplaza el grupo "Lo último" (ticker +
  miniaturas) de alpha.17 — una sola noticia principal con el texto
  (etiqueta/titular/bajada/hora) superpuesto a la fotografía sobre un
  degradado oscuro (`.hero__scrim`), más dos noticias secundarias al lado.
  `HomeContentProvider::HERO_TOTAL` bajó de 7 a 3 (hero + 2, ya no hero + 6
  miniaturas); campo `heroSecondary` reemplaza a `heroThumbs`/`aside`.
- **`Support\RelativeDate::forPost()`**: "Hace 2 horas" vía `human_time_diff()`
  de WordPress core (ya localizado si el sitio tiene instalado el locale
  es_CO/es_ES — `wp language core install es_CO --activate`, imprescindible
  en el sitio real para que esto y el resto de cadenas de WordPress core
  salgan en español) — reutilizado por el hero y las tarjetas de noticia.
  Opinión y Edición Impresa siguen mostrando fecha absoluta a propósito.
- **Judiciales pasó de un mosaico de 9 a 4** (lo que pidió esta vez el
  cliente) y "Editorial" se retiró como bloque propio de portada — el listado
  de secciones ahora es exactamente Hero/La Guajira/Judiciales/Opinión/Más
  noticias/Lo más leído/Edición Impresa/Newsletter; la categoría `editorial`
  se sigue sembrando (por si el equipo la usa en otro lugar) pero ya no tiene
  una sección dedicada en la portada.
- **Opinión con identidad de columnista**: retrato circular
  (`get_avatar()`), nombre, cargo/nombre de columna (biografía del autor en
  WordPress — el mismo campo sirve para "Director" o para el nombre de una
  columna tipo "Entre el río y el mar", a discreción del equipo editorial) y
  extracto — no una parrilla más de tarjetas.
- **"Lo más leído" con tres ventanas de tiempo** (24h/7d/30d): las tres
  listas se calculan y se imprimen todas en el HTML de una sola vez
  (`HomeContentProvider::mostReadByWindow()`); el filtro en pantalla
  (`initMostReadFilter()`, `assets/js/app.js`) solo muestra/oculta con
  `[hidden]`, sin volver a pedir nada al servidor — evita sumar un endpoint
  REST nuevo solo para esto. Deliberadamente después de "Más noticias" en la
  portada, nunca compite con el hero.
- **Edición Impresa reforzada**: portada más grande + dos acciones ("Ver
  edición digital" al propio artículo, "Descargar PDF" al primer PDF
  adjunto al post en la Biblioteca de medios — `firstAttachedPdfUrl()`, sin
  botón si no hay ninguno). **Excluida de los pools de "más recientes"**
  (`category__not_in` en `recentPosts()`): al publicarse con fecha muy
  reciente cada vez, sin esto se colaba como hero o en "Más noticias" por
  ser el post más nuevo, desplazando una noticia real — bug real encontrado
  en la verificación con datos de ejemplo, cubierto por
  `HomeContentProviderTest::test_content_never_uses_the_edicion_impresa_post_as_hero_or_in_the_news_grid`.
- **Newsletter real, no un formulario decorativo**: `dnorte-core` suma un
  módulo nuevo completo — `Newsletter\Subscribers\NewsletterSubscriberRepository`
  (tabla propia, UNIQUE en email para deduplicar), `Newsletter\NewsletterController`
  (`POST /wp-json/dnorte/v1/newsletter/subscribe`, valida con `is_email()`) y
  `Newsletter\NewsletterAdminPage` (panel de solo lectura con el conteo y los
  últimos 200 suscriptores) — mismo patrón que Analítica. El formulario de
  portada (`newsletter.php`) envía por `fetch()` (`initNewsletterForm()`);
  sin JS, el `<form>` ya apunta a esa misma URL con `method="post"` normal.
- **Navegación móvil de dos niveles**: `header.php` representa la misma
  ubicación de menú `primary` dos veces — la de siempre (`.main-navigation`,
  completa, con "Más" desplegado, detrás del botón ☰) y una nueva
  (`.mobile-quick-nav`, `depth=1` para que "Más" nunca imprima sus nueve
  hijos ahí) en una tira horizontal siempre visible en móvil. WordPress no
  tiene problema en renderizar la misma ubicación dos veces en la misma
  petición con argumentos distintos. Un botón nuevo (🔔, `#suscribete`) enlaza
  directo a la sección de newsletter.
- **Pie de página de cuatro columnas** (Secciones/Institucional/Legal/Redes
  sociales) — cada una es un `<details>` nativo: en escritorio el CSS fuerza
  el resumen siempre abierto (`pointer-events: none`, sin flecha); en móvil
  el navegador ya sabe plegar/desplegar un `<details>` normal — "acordeón en
  móvil" sin una sola línea de JS propia. "Secciones" enlaza directo a las
  siete categorías reales (no un menú aparte que sincronizar a mano);
  "Institucional" reutiliza la ubicación de menú que antes se llamaba
  `footer_sites` (renombrada — ya no existe el concepto "nuestros sitios" de
  alpha.17); "Legal" reutiliza la ubicación `footer` de siempre. Cualquier
  columna sin datos configurados simplemente no se imprime.
- **Bug real de layout encontrado en la verificación móvil**: la tercera
  columna de `.site-header__inner` tenía un ancho fijo de un solo botón
  (`2.25rem`) desde alpha.17, pero ahora son tres iconos (🔔/buscar/tema) —
  se salían de su columna y se solapaban con el logo en pantallas angostas.
  Corregido con `grid-template-columns: 2.25rem 1fr auto` (columna de
  acciones a ancho de contenido, no fijo).

## `Admin\AdminPage::$parentSlug` — ver "Menú de administración"

Con tres módulos de administración ya registrados (Turnos, Analítica,
Publicidad, cada uno con su propia entrada de nivel superior gracias al fix de
`v0.1.0-alpha.11`), este es el patrón a seguir para cualquier módulo nuevo: un
`AdminPage` con `parentSlug: null` para su propia entrada, o con el slug de un
módulo existente para anidarse como submenú — nunca depender de la posición
relativa entre módulos sin relación.

## Qué falta por decidir/documentar aquí

A medida que se añadan más módulos (IA, caché, seguridad, ...) documentar en este
mismo fichero las decisiones de diseño y qué se reimplementa vs. qué se reutiliza
de WordPress core, siguiendo el mismo criterio que `handoff-nd-platform.md` §4.
