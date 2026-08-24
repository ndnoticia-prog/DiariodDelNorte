<?php
/**
 * Cabecera del sitio.
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<script>
		// Anti-parpadeo de modo oscuro: se ejecuta antes del primer paint, en el <head>,
		// para que data-theme ya esté puesto cuando el CSS se aplica (evita el flash de
		// tema claro en un visitante con preferencia oscura guardada).
		(function () {
			var stored = localStorage.getItem('dnorte-theme');
			var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
			document.documentElement.setAttribute('data-theme', theme);
		})();
	</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#contenido-principal">
	<?php esc_html_e( 'Saltar al contenido', 'dnorte-theme' ); ?>
</a>

<?php
/**
 * Punto de enganche para dnorte-core (Ads\Providers\AdsServiceProvider, espacio
 * "cabecera") — después del skip-link a propósito, para no quitarle el primer
 * lugar como elemento enfocable de accesibilidad.
 */
do_action( 'dnorte_theme/before_topbar' );
?>

<div class="site-topbar">
	<div class="site-topbar__inner">
		<?php
		$topbarLocation = get_theme_mod( 'dnorte_topbar_location', __( 'Riohacha, La Guajira', 'dnorte-theme' ) );
		if ( is_string( $topbarLocation ) && trim( $topbarLocation ) !== '' ) :
			?>
			<span class="site-topbar__location"><?php echo esc_html( $topbarLocation ); ?></span>
			<span class="site-topbar__sep" aria-hidden="true">·</span>
		<?php endif; ?>
		<span class="site-topbar__date">
			<?php
			// Formato fijo tipo cabecera de diario ("17 de agosto de 2026"),
			// independiente del "date_format" configurable en Ajustes → General
			// (ese es para fechas de publicación de artículos, no para esto).
			echo esc_html( date_i18n( 'j \d\e F \d\e Y' ) );
			?>
		</span>
	</div>
</div>

<header id="masthead" class="site-header" role="banner">
	<div class="site-header__inner">
		<button type="button" class="nav-toggle" aria-expanded="false" aria-controls="site-navigation">
			<span class="screen-reader-text"><?php esc_html_e( 'Abrir menú', 'dnorte-theme' ); ?></span>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<path d="M3 6h18M3 12h18M3 18h18" stroke-linecap="round" />
			</svg>
		</button>

		<div class="site-branding">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php elseif ( is_readable( DNORTE_THEME_DIR . '/assets/images/dnorte-logo.png' ) ) : ?>
					<img src="<?php echo esc_url( DNORTE_THEME_URI . '/assets/images/dnorte-logo.png' ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="280" height="60" />
				<?php else : ?>
					<span class="site-branding__text"><?php bloginfo( 'name' ); ?></span>
					<?php if ( get_bloginfo( 'description' ) !== '' ) : ?>
						<span class="site-branding__tagline"><?php bloginfo( 'description' ); ?></span>
					<?php endif; ?>
				<?php endif; ?>
			</a>
		</div>

		<div class="site-header__actions">
			<a class="icon-button subscribe-link" href="#suscribete" aria-label="<?php esc_attr_e( 'Suscribirse al newsletter', 'dnorte-theme' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" stroke-linecap="round" stroke-linejoin="round" />
					<path d="M13.73 21a2 2 0 0 1-3.46 0" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</a>

			<button type="button" class="icon-button search-toggle" aria-expanded="false" aria-label="<?php esc_attr_e( 'Buscar', 'dnorte-theme' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<circle cx="11" cy="11" r="7" />
					<path d="m21 21-4.3-4.3" stroke-linecap="round" />
				</svg>
			</button>

			<button type="button" class="icon-button theme-toggle" aria-label="<?php esc_attr_e( 'Cambiar a modo oscuro', 'dnorte-theme' ); ?>">
				<svg class="theme-toggle__icon theme-toggle__icon--light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<circle cx="12" cy="12" r="4" />
					<path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke-linecap="round" />
				</svg>
				<svg class="theme-toggle__icon theme-toggle__icon--dark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
					<path d="M20.354 15.354A9 9 0 0 1 8.646 3.646 9.003 9.003 0 1 0 20.354 15.354Z" />
				</svg>
			</button>
		</div>
	</div>

	<div class="site-header__search">
		<?php get_search_form(); ?>
	</div>
</header>

<nav class="primary-nav" role="navigation" aria-label="<?php esc_attr_e( 'Menú principal', 'dnorte-theme' ); ?>">
	<div id="site-navigation" class="main-navigation">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'fallback_cb'    => false,
			)
		);
		?>
	</div>
</nav>

<?php
/**
 * Segunda representación del mismo menú "primary" — solo de primer nivel
 * (depth=1, así "Más" nunca imprime sus nueve hijos aquí) en una tira
 * horizontal deslizable, siempre visible en móvil sin depender del botón ☰.
 * El menú completo (con "Más" desplegado) sigue disponible detrás del botón
 * ☰ (.main-navigation de arriba) para quien lo prefiera. WordPress no tiene
 * problema en representar la misma ubicación de menú dos veces en la misma
 * petición con argumentos distintos.
 */
wp_nav_menu(
	array(
		'theme_location'       => 'primary',
		'container'            => 'nav',
		'container_class'      => 'mobile-quick-nav',
		'container_aria_label' => __( 'Categorías', 'dnorte-theme' ),
		'menu_class'           => 'mobile-quick-nav__list',
		'depth'                => 1,
		'fallback_cb'          => false,
	)
);
?>

<?php
/**
 * Punto de enganche para dnorte-core (Ads\Providers\AdsServiceProvider, espacio
 * "inicio") — justo debajo del menú, antes de <main>.
 */
do_action( 'dnorte_theme/after_header' );
?>
