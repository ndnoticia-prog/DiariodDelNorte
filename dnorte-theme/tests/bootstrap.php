<?php
/**
 * Bootstrap de las pruebas unitarias (Brain Monkey, sin WordPress real).
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! defined( 'DNORTE_THEME_VERSION' ) ) {
	define( 'DNORTE_THEME_VERSION', '0.1.0-alpha.1' );
}

if ( ! defined( 'DNORTE_THEME_DIR' ) ) {
	define( 'DNORTE_THEME_DIR', dirname( __DIR__ ) );
}

if ( ! defined( 'DNORTE_THEME_URI' ) ) {
	define( 'DNORTE_THEME_URI', 'http://example.test/wp-content/themes/dnorte-theme' );
}
