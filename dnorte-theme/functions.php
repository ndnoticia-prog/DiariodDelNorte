<?php
/**
 * Bootstrap del tema. No contiene lógica de negocio: solo comprueba que dnorte-core
 * esté activo y se registra en su filtro de providers.
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Sin acceso directo.
}

define( 'DNORTE_THEME_VERSION', '0.1.0-alpha.1' );
define( 'DNORTE_THEME_DIR', get_template_directory() );
define( 'DNORTE_THEME_URI', get_template_directory_uri() );

spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'DNorteTheme\\';

		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$path     = DNORTE_THEME_DIR . '/src/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require $path;
		}
	}
);

/**
 * dnorte-core carga en `plugins_loaded`; este `functions.php` se carga después
 * (al resolver el tema activo), pero SIEMPRE antes de `after_setup_theme`, que es
 * cuando Application::boot() realmente arranca (ver dnorte-core.php). Por eso es
 * seguro engancharse aquí, en el nivel superior del fichero, sin envolver en ningún hook.
 */
if ( class_exists( 'DNorteCore\\Application' ) ) {
	add_filter(
		'dnorte_core/providers',
		static function ( array $providers ): array {
			$providers[] = \DNorteTheme\Providers\ThemeServiceProvider::class;

			return $providers;
		}
	);
} else {
	// dnorte-core no está activo: degradar sin fatal error, solo avisar en el admin.
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'DNorte Theme requiere que el plugin DNorte Core esté activo.', 'dnorte-theme' );
			echo '</p></div>';
		}
	);
}
