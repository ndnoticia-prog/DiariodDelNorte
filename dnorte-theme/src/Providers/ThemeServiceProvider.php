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
use DNorteTheme\Content\DefaultContentSeeder;

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
		$hooks->addAction( 'after_setup_theme', $this->registerImageSizes( ... ), 10 );
		// No en after_switch_theme: ese hook solo dispara al CAMBIAR de tema, nunca en
		// una actualización de versión de un tema ya activo (justo el caso real de
		// desplegar esta versión sobre un sitio que ya tiene dnorte-theme puesto) —
		// hallazgo aplicado desde el día uno de este bloque, no un bug corregido después.
		// DefaultContentSeeder::seed() se protege solo con su propia opción "ya sembrado",
		// así que engancharlo aquí (cada carga) es seguro y barato.
		$hooks->addAction( 'after_setup_theme', $this->seedDefaultContent( ... ), 20 );
		$hooks->addAction( 'wp_enqueue_scripts', $this->enqueueAssets( ... ), 10 );
		$hooks->addAction( 'customize_register', $this->registerCustomizer( ... ), 10 );
	}

	public function registerThemeSupports(): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'responsive-embeds' );
		// Ancho/alto orientativos (el logo real es ~4.7:1) — flex en ambos ejes porque
		// el archivo real que se suba puede no respetar exactamente esa proporción.
		add_theme_support(
			'custom-logo',
			array(
				'width'       => 400,
				'height'      => 110,
				'flex-width'  => true,
				'flex-height' => true,
			)
		);
	}

	public function registerMenus(): void {
		register_nav_menus(
			array(
				'primary'      => __( 'Menú principal', 'dnorte-theme' ),
				'footer'       => __( 'Pie de página — legal', 'dnorte-theme' ),
				'footer_sites' => __( 'Pie de página — nuestros sitios', 'dnorte-theme' ),
			)
		);
	}

	/**
	 * Tamaños propios del tema para la maqueta de portada — 'dnorte-featured'
	 * (1200×675, la exige Google Discover) ya la registra dnorte-core\Media, esta
	 * clase no la duplica.
	 */
	public function registerImageSizes(): void {
		add_image_size( 'dnorte-card', 480, 360, true );
		add_image_size( 'dnorte-thumb', 160, 160, true );
	}

	public function seedDefaultContent(): void {
		( new DefaultContentSeeder() )->seed();
	}

	/**
	 * Ajustes editables por el cliente sin tocar código: ubicación de la cabecera,
	 * número de WhatsApp del banner (Ads no cubre este espacio — no es ninguno de
	 * los cinco slots de publicidad, ver docs/Architecture.md) y redes sociales del
	 * pie. Ninguno tiene un valor por defecto que simule un contacto real: si el
	 * cliente no lo rellena, el bloque correspondiente simplemente no se imprime
	 * (ver promo-banner.php / footer.php).
	 */
	public function registerCustomizer( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section(
			'dnorte_site_info',
			array(
				'title'    => __( 'Diario del Norte', 'dnorte-theme' ),
				'priority' => 30,
			)
		);

		$wp_customize->add_setting( 'dnorte_topbar_location', array( 'default' => __( 'Riohacha, La Guajira', 'dnorte-theme' ) ) );
		$wp_customize->add_control(
			'dnorte_topbar_location',
			array(
				'section' => 'dnorte_site_info',
				'label'   => __( 'Ciudad en la barra superior', 'dnorte-theme' ),
				'type'    => 'text',
			)
		);

		$wp_customize->add_setting( 'dnorte_whatsapp_number', array( 'default' => '' ) );
		$wp_customize->add_control(
			'dnorte_whatsapp_number',
			array(
				'section'     => 'dnorte_site_info',
				'label'       => __( 'Número de WhatsApp (con indicativo, solo dígitos)', 'dnorte-theme' ),
				'description' => __( 'Ej. 573114567890. Vacío = el banner de WhatsApp de portada no se muestra.', 'dnorte-theme' ),
				'type'        => 'text',
			)
		);

		foreach ( array(
			'dnorte_social_facebook'  => __( 'Facebook (URL)', 'dnorte-theme' ),
			'dnorte_social_x'         => __( 'X / Twitter (URL)', 'dnorte-theme' ),
			'dnorte_social_instagram' => __( 'Instagram (URL)', 'dnorte-theme' ),
		) as $setting => $label ) {
			$wp_customize->add_setting( $setting, array( 'default' => '' ) );
			$wp_customize->add_control(
				$setting,
				array(
					'section' => 'dnorte_site_info',
					'label'   => $label,
					'type'    => 'url',
				)
			);
		}
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
