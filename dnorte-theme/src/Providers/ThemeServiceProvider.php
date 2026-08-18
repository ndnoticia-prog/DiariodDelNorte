<?php
/**
 * Provider del tema: theme supports, menús, encolado de assets. Sin lógica de negocio
 * editorial — eso vive en dnorte-core y en los módulos que se vayan añadiendo.
 *
 * @package DNorteTheme\Providers
 */

declare(strict_types=1);

namespace DNorteTheme\Providers;

use DNorteCore\Hooks\HookManager;
use DNorteCore\Providers\ServiceProvider;

final class ThemeServiceProvider extends ServiceProvider {

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		// register_nav_menus() es una función que hay que LLAMAR en after_setup_theme,
		// no un hook al que engancharse (no existe tal action en WordPress core) — bug
		// real encontrado y corregido en la primera verificación en navegador de este
		// scaffold: sin esto, wp-admin/nav-menus.php reporta "tu tema no soporta menús".
		$hooks->addAction( 'after_setup_theme', $this->registerThemeSupports( ... ), 10 );
		$hooks->addAction( 'after_setup_theme', $this->registerMenus( ... ), 10 );
		$hooks->addAction( 'wp_enqueue_scripts', $this->enqueueAssets( ... ), 10 );
	}

	public function registerThemeSupports(): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'responsive-embeds' );
	}

	public function registerMenus(): void {
		register_nav_menus(
			array(
				'primary' => __( 'Menú principal', 'dnorte-theme' ),
				'footer'  => __( 'Menú de pie de página', 'dnorte-theme' ),
			)
		);
	}

	public function enqueueAssets(): void {
		$distManifest = DNORTE_THEME_DIR . '/dist/app.css';

		if ( is_readable( $distManifest ) ) {
			wp_enqueue_style( 'dnorte-theme-app', DNORTE_THEME_URI . '/dist/app.css', array(), DNORTE_THEME_VERSION );
		}

		$distScript = DNORTE_THEME_DIR . '/dist/app.js';

		if ( is_readable( $distScript ) ) {
			wp_enqueue_script( 'dnorte-theme-app', DNORTE_THEME_URI . '/dist/app.js', array(), DNORTE_THEME_VERSION, true );
		}
	}
}
