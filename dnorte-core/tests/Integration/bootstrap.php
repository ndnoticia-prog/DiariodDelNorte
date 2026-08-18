<?php
/**
 * Bootstrap de las pruebas de integración: WordPress real + MySQL/MariaDB real,
 * siguiendo el flujo oficial de wordpress-develop (sin Docker/wp-env, no disponible
 * en este entorno). Ver tools/wp-tests/README.md para el paso a paso completo.
 *
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

// Localiza el checkout compartido de wordpress-develop (dos niveles por encima de
// packages/dnorte-core: tools/wp-tests/wordpress-develop), salvo que se exporte
// WP_TESTS_DIR para usar una ubicación distinta.
$wpTestsDir = getenv( 'WP_TESTS_DIR' );

if ( $wpTestsDir === false ) {
	$wpTestsDir = dirname( __DIR__, 3 ) . '/tools/wp-tests/wordpress-develop/tests/phpunit';
}

require_once $wpTestsDir . '/includes/functions.php';

/**
 * Carga dnorte-core como si fuera un mu-plugin — exactamente igual que en producción,
 * porque Application::resolveProviderClasses() ya registra cada ServiceProvider con un
 * simple class_exists(). No hace falta ningún truco especial para "activarlo".
 */
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__, 2 ) . '/dnorte-core.php';
	}
);

require $wpTestsDir . '/includes/bootstrap.php';
