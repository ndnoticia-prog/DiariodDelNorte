<?php

/**
 * PHPStan no puede descubrir constantes definidas vía define() en un
 * archivo (functions.php) cuando se usan en otro (src/*) sin ejecutarlas
 * primero; este bootstrap solo las declara para el análisis estático, con
 * los mismos valores de marcador de posición que usa tests/bootstrap.php.
 *
 * @see https://phpstan.org/user-guide/discovering-symbols
 */

declare(strict_types=1);

if ( ! defined( 'DNORTE_THEME_VERSION' ) ) {
	define( 'DNORTE_THEME_VERSION', '0.1.0-alpha.1' );
}

if ( ! defined( 'DNORTE_THEME_DIR' ) ) {
	define( 'DNORTE_THEME_DIR', __DIR__ . '/' );
}

if ( ! defined( 'DNORTE_THEME_URI' ) ) {
	define( 'DNORTE_THEME_URI', 'https://example.test/wp-content/themes/dnorte-theme' );
}
