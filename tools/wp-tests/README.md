# Entorno de pruebas de integración con WordPress real

`dnorte-core` y `dnorte-theme` tienen dos suites de PHPUnit distintas cada uno:

- **Unitarias** (`composer test`, `tests/Unit/`): usan Brain Monkey para interceptar
  funciones de WordPress. Rápidas, sin base de datos, pero no pueden cubrir código que
  depende de `wpdb`/`WP_Post`/`WP_Query` reales (`DatabaseManager`, `Migrator`,
  `Installer`; el `handle()` de los controladores REST).
- **Integración** (`composer test:integration`, `tests/Integration/`): cargan un
  núcleo real de WordPress contra una base de datos MySQL/MariaDB de pruebas, siguiendo
  el flujo oficial de [`tests/phpunit/includes/bootstrap.php`](https://github.com/WordPress/wordpress-develop/blob/trunk/tests/phpunit/includes/bootstrap.php)
  de `wordpress-develop` (instalación parcial, sin Docker/`wp-env`).

Mismo enfoque que usó ND Platform (ver `docs/handoff-nd-platform.md` §6), adaptado a
este repositorio.

## Preparar el entorno (una sola vez)

1. **MySQL/MariaDB en ejecución**, con una base de datos vacía y un usuario con permisos
   sobre ella.

   ```bash
   /Users/delvisiban/.nd-toolchain/homebrew/Cellar/mariadb/12.3.2/bin/mariadbd-safe \
     --datadir='/Users/delvisiban/.nd-toolchain/homebrew/var/mysql' &
   ```

   Usuario/base ya creados en este entorno: `wp` / `wp_local_dev_pw` sobre
   `dnorte_platform_test` (host `127.0.0.1`) — nombre de base distinto al
   `wordpress_test` que usa el proyecto ND Platform, para no compartir estado entre dos
   repositorios distintos aunque reutilicen el mismo servidor MariaDB local.

2. **Checkout de `wordpress-develop`** (núcleo de WordPress + el arnés de pruebas
   PHPUnit, en sparse-checkout para no bajar el repo completo):

   ```bash
   mkdir -p tools/wp-tests && cd tools/wp-tests
   git clone --filter=blob:none --sparse --depth=1 https://github.com/WordPress/wordpress-develop.git
   cd wordpress-develop
   git sparse-checkout set --skip-checks src tests/phpunit wp-tests-config-sample.php
   ```

3. **`wp-tests-config.php`**: copiar `wp-tests-config-sample.php` a
   `wp-tests-config.php` dentro de `wordpress-develop/` y rellenar
   `DB_NAME`/`DB_USER`/`DB_PASSWORD`/`DB_HOST` con las credenciales del paso 1, y
   generar salts únicos (`php -r '...random_bytes...'` o el
   [generador de WordPress.org](https://api.wordpress.org/secret-key/1.1/salt/)).

4. **`tools/wp-tests/phpunit9/`**: PHPUnit 9 aislado y compartido — instalarlo una vez:

   ```bash
   cd tools/wp-tests/phpunit9
   composer install
   ```

## Ejecutar las pruebas de integración

```bash
cd dnorte-core && composer test:integration
cd dnorte-theme && composer test:integration
```

Localiza el checkout compartido automáticamente (dos niveles por encima de cada
paquete: `tools/wp-tests/wordpress-develop`). Para usar una ubicación distinta, exporta
`WP_TESTS_DIR`:

```bash
WP_TESTS_DIR=/ruta/a/otro/checkout/tests/phpunit composer test:integration
```

## Cómo se activan dnorte-core/dnorte-theme en las pruebas

`dnorte-core/tests/Integration/bootstrap.php` engancha
`tests_add_filter('muplugins_loaded', ...)` para requerir `dnorte-core.php`
directamente — igual que en producción, sin ningún truco especial:
`Application::resolveProviderClasses()` ya registra cada `ServiceProvider` con un
simple `class_exists()`. `dnorte-theme` no se activa como tema en su propio arnés: sus
clases con cobertura de integración (`HomeContentProvider`) son consultas `WP_Query`
planas, autoloadeables directamente vía el mapeo PSR-4 de `tools/wp-tests/phpunit9/`
sin pasar por `functions.php`.

## Un solo PHPUnit 9 compartido por ambos paquetes

`tools/wp-tests/phpunit9/composer.json` mapea `DNorteCore\\` y `DNorteTheme\\`
directamente a su `src/` respectivo por ruta — el mismo "meta-proyecto" Composer,
compartido, para los dos paquetes que necesiten pruebas de integración, en vez de
duplicar la instalación de PHPUnit 9 en cada uno. Mismo patrón que
`tools/wp-tests/phpunit9/` en ND Platform (que además comparte 11 paquetes, no solo
dos).

## Lección real: la instalación ya corrió antes de que arranque cualquier test

`CoreServiceProvider::maybeRunUpgrade()` (enganchado a `init`) ejecuta
`Installer::install()` automáticamente en cada arranque si `dnorte_core_installed_version`
no coincide con `DNORTE_CORE_VERSION` — exactamente igual que en un sitio real. Esto
significa que la tabla `dnorte_migrations` **ya existe** para cuando cualquier clase de
prueba arranca. Un test que la cree o la vacíe por completo (en vez de limpiar solo sus
propias filas) rompe esa invariante para el resto de la suite, porque esa tabla persiste
en la base de datos compartida entre invocaciones separadas del proceso de PHPUnit — a
diferencia de las filas insertadas *dentro* de un test individual, que si se revierten
automáticamente por el aislamiento transaccional de `WP_UnitTestCase`. Ver el docblock
de `MigratorTest` para el patrón completo (limpieza explícita de la fila propia, nunca
`DROP TABLE`).

## CREATE/DROP TABLE producen un COMMIT implícito

Cualquier DDL (usado por `DatabaseManagerTest` para su tabla de fixtures propia) hace
un `COMMIT` implícito en MySQL/MariaDB, rompiendo el aislamiento transaccional habitual
de `WP_UnitTestCase` para el resto de esa prueba. Por eso `DatabaseManagerTest` crea y
elimina su tabla de fixtures en `wpSetUpBeforeClass()`/`wpTearDownAfterClass()` (una
sola vez para toda la clase, fuera de cualquier transacción por-test), no dentro de un
test individual.

## Por qué no `wp-env` (Docker)

`wp-env` es la forma recomendada por WordPress.org para pruebas de integración, pero
requiere Docker Desktop, no disponible en este entorno de desarrollo. Se usa en su
lugar el flujo "clásico" documentado directamente por el propio arnés de pruebas de
WordPress, con `git sparse-checkout` en vez de `svn` (tampoco disponible). El resultado
es equivalente: mismo núcleo de WordPress, mismo `WP_UnitTestCase`, misma base de datos
real.
