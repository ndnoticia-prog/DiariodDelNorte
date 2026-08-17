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
	<meta charset="<?php bloginfo('charset'); ?>" />
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
	<?php esc_html_e('Saltar al contenido', 'dnorte-theme'); ?>
</a>

<header id="masthead" class="site-header" role="banner">
	<div class="site-branding">
		<?php if (has_custom_logo()) : ?>
			<?php the_custom_logo(); ?>
		<?php else : ?>
			<a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>
		<?php endif; ?>
	</div>

	<nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_attr_e('Menú principal', 'dnorte-theme'); ?>">
		<?php
		wp_nav_menu([
			'theme_location' => 'primary',
			'container' => false,
			'fallback_cb' => false,
		]);
		?>
	</nav>
</header>
