<?php
/**
 * Plugin Name:       DNorte Core
 * Plugin URI:        https://diariodelnorte.net/
 * Description:       Núcleo de la plataforma editorial de Diario del Norte: contenedor DI, configuración, hooks, eventos y orquestación de módulos. Requiere activar dnorte-theme para el front-end.
 * Version:           0.1.0-alpha.1
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

define( 'DNORTE_CORE_VERSION', '0.1.0-alpha.1' );
define( 'DNORTE_CORE_FILE', __FILE__ );
define( 'DNORTE_CORE_DIR', __DIR__ );

// Autoload: usa el autoloader de Composer si existe (composer install ya corrido);
// si no, registra un autoloader PSR-4 mínimo propio para DNorteCore\ → src/, para que
// el plugin funcione recién clonado sin depender de que composer install se haya
// ejecutado todavía.
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
 * el Container. No hay migraciones propias todavía; esto solo deja lista la tabla de
 * seguimiento (`{prefix}dnorte_migrations`) y registra la versión instalada, para que
 * futuros módulos ya tengan la infraestructura sobre la que sumar sus migraciones.
 */
register_activation_hook(
	__FILE__,
	static function (): void {
		global $wpdb;

		$database  = new \DNorteCore\Database\DatabaseManager( $wpdb );
		$migrator  = new \DNorteCore\Migrator\Migrator( $database );
		$installer = new \DNorteCore\Installer\Installer( $migrator );

		$installer->install( array(), DNORTE_CORE_VERSION );
	}
);
