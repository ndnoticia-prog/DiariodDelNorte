<?php
/**
 * Portada del sitio, basada en bloques con contenido real
 * (dnorte-theme\Content\HomeContentProvider).
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

get_header();

$content = ( new \DNorteTheme\Content\HomeContentProvider() )->content();
?>

<main id="contenido-principal" class="site-main" role="main">
	<?php if ( $content['hero'] instanceof WP_Post ) : ?>
		<?php get_template_part( 'template-parts/blocks/hero', null, array( 'post' => $content['hero'] ) ); ?>
	<?php endif; ?>

	<?php if ( $content['breaking'] !== array() ) : ?>
		<?php get_template_part( 'template-parts/blocks/breaking', null, array( 'posts' => $content['breaking'] ) ); ?>
	<?php endif; ?>

	<?php if ( $content['latest'] !== array() ) : ?>
		<?php get_template_part( 'template-parts/blocks/latest-grid', null, array( 'posts' => $content['latest'] ) ); ?>
	<?php endif; ?>

	<?php if ( $content['hero'] === null ) : ?>
		<p><?php esc_html_e( 'Todavía no hay artículos publicados.', 'dnorte-theme' ); ?></p>
	<?php endif; ?>
</main>

<?php
get_footer();
