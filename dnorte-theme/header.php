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

<div class="site-topbar">
	<div class="site-topbar__inner">
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
		<div class="site-branding">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
			<?php endif; ?>
		</div>

		<div class="header-controls">
			<?php get_search_form(); ?>

			<nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Menú principal', 'dnorte-theme' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'fallback_cb'    => false,
					)
				);
				?>
			</nav>

			<button type="button" class="nav-toggle" aria-expanded="false" aria-controls="site-navigation">
				<span class="screen-reader-text"><?php esc_html_e( 'Abrir menú', 'dnorte-theme' ); ?></span>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<path d="M3 6h18M3 12h18M3 18h18" stroke-linecap="round" />
				</svg>
			</button>

			<button type="button" class="theme-toggle" aria-label="<?php esc_attr_e( 'Cambiar a modo oscuro', 'dnorte-theme' ); ?>">
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
</header>
