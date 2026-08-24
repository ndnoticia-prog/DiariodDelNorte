<?php
/**
 * Plugin Name:       DNorte Core
 * Plugin URI:        https://diariodelnorte.net/
 * Description:       Núcleo de la plataforma editorial de Diario del Norte: contenedor DI, configuración, hooks, eventos y orquestación de módulos. Requiere activar dnorte-theme para el front-end.
 * Version:           0.1.0-alpha.16
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Diario del Norte
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dnorte-core
 *
 * @package DNorteCore
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Sin acceso directo.
}

define( 'DNORTE_CORE_VERSION', '0.1.0-alpha.16' );
define( 'DNORTE_CORE_FILE', __FILE__ );
define( 'DNORTE_CORE_DIR', __DIR__ );

// Autoload: usa el autoloader de Composer si existe (composer install ya corrido);
// si no, registra un autoloader PSR-4 mínimo propio para DNorteCore\ → src/, para que
// el plugin funcione recién clonado sin depender de que composer install se haya
// ejecutado todavía.
//
// El class_exists() exterior evita volver a declarar las clases del plugin si ya las
// cargó otro autoloader en el mismo proceso PHP — el caso real es el arnés de pruebas
// de integración (tools/wp-tests/phpunit9/), que mapea este mismo namespace
// directamente a src/ por ruta para no cargar el PHPUnit 10 del vendor/ de este
// paquete dentro de un proceso que ya corre bajo PHPUnit 9 (ver
// tools/wp-tests/README.md). Sin este guard, requerir vendor/autoload.php ahí
// declararía las clases de PHPUnit 10 dos veces y produciría un fatal error.
if ( ! class_exists( 'DNorteCore\\Application' ) ) {
	if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	} else {
		spl_autoload_register(
			static function ( string $class ): void {
				$prefix = 'DNorteCore\\';

				if ( ! str_starts_with( $class, $prefix ) ) {
					return;
				}

				$relative = substr( $class, strlen( $prefix ) );
				$path     = __DIR__ . '/src/' . str_replace( '\\', '/', $relative ) . '.php';

				if ( is_readable( $path ) ) {
					require $path;
				}
			}
		);
	}
}

/**
 * Arranque diferido a `after_setup_theme` (no `plugins_loaded`): el tema activo se
 * auto-registra en el filtro `dnorte_core/providers` desde su propio `functions.php`,
 * que WordPress carga después de `plugins_loaded` pero antes de `after_setup_theme`.
 * Arrancar antes perdería silenciosamente los providers del tema — ver Application.php.
 */
add_action(
	'after_setup_theme',
	static function (): void {
		\DNorteCore\Application::instance( DNORTE_CORE_FILE )->boot();
	},
	5
);

/**
 * register_activation_hook() debe llamarse desde el archivo principal del plugin en
 * tiempo de carga (no se puede diferir a Application::boot(), que corre en
 * after_setup_theme) — por eso construye sus dependencias a mano en vez de pasar por
 * el Container. Installer\MigrationRegistry::all() es la lista central de
 * migraciones (ver su docblock: existe porque a esta altura Application todavía no
 * arrancó y no puede resolver providers dinámicamente).
 */
register_activation_hook(
	__FILE__,
	static function (): void {
		global $wpdb;

		$database  = new \DNorteCore\Database\DatabaseManager( $wpdb );
		$migrator  = new \DNorteCore\Migrator\Migrator( $database );
		$installer = new \DNorteCore\Installer\Installer( $migrator );

		$installer->install( \DNorteCore\Installer\MigrationRegistry::all(), DNORTE_CORE_VERSION );

		// Intento de mejor esfuerzo: la regla de rewrite de /sitemap-news.xml se
		// registra en 'init' (SeoServiceProvider), un hook que corre DESPUÉS de este
		// callback de activación (limitación conocida de WordPress, no de este
		// plugin) — así que este flush casi siempre ocurre antes de que la regla
		// nueva exista y no la incluye. Si /sitemap-news.xml da 404 justo tras
		// activar, basta con ir a Ajustes → Enlaces permanentes y pulsar "Guardar
		// cambios" una vez (WordPress vuelve a generar las reglas con el sitemap de
		// noticias ya registrado). Mismo caso ya documentado en ND Platform para
		// el mismo tipo de sitemap.
		flush_rewrite_rules();
	}
);
