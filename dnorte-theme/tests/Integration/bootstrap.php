<?php
/**
 * Bootstrap de las pruebas de integración de dnorte-theme: WordPress real, sin
 * Docker/wp-env. A diferencia de dnorte-core, no hace falta activar el plugin ni el
 * tema — las clases de dnorte-theme que necesitan cobertura de integración
 * (HomeContentProvider) son consultas WP_Query planas, autoloadeables directamente
 * vía el arnés compartido (tools/wp-tests/phpunit9/) sin pasar por functions.php.
 *
 * @package DNorteTheme\Tests\Integration
 */

declare(strict_types=1);

$wpTestsDir = getenv( 'WP_TESTS_DIR' );

if ( $wpTestsDir === false ) {
	$wpTestsDir = dirname( __DIR__, 3 ) . '/tools/wp-tests/wordpress-develop/tests/phpunit';
}

require_once $wpTestsDir . '/includes/functions.php';
require $wpTestsDir . '/includes/bootstrap.php';
