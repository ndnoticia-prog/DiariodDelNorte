<?php
/**
 * Portada del sitio — maqueta real pedida por el cliente (v0.1.0-alpha.17):
 * "Lo último" (hero + miniaturas + columna), La Guajira, Opinión, banner de
 * WhatsApp, Judiciales/Editorial/Lo más leído en tres columnas, y "Más
 * noticias" con "Cargar más". Todo el contenido viene de
 * DNorteTheme\Content\HomeContentProvider — esta plantilla solo decide qué
 * bloque imprimir con qué datos, sin ninguna consulta propia.
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

get_header();

$content = ( new \DNorteTheme\Content\HomeContentProvider() )->content();
?>

<main id="contenido-principal" class="site-main" role="main">
	<?php if ( $content['hero'] instanceof WP_Post ) : ?>
		<?php
		get_template_part(
			'template-parts/blocks/hero-carousel',
			null,
			array(
				'hero'   => $content['hero'],
				'thumbs' => $content['heroThumbs'],
				'aside'  => $content['aside'],
			)
		);
		?>
	<?php endif; ?>

	<?php
	get_template_part(
		'template-parts/blocks/category-block',
		null,
		array(
			'heading'  => __( 'La Guajira', 'dnorte-theme' ),
			'mode'     => 'featured-list',
			'featured' => $content['laGuajiraFeatured'],
			'list'     => $content['laGuajiraList'],
		)
	);
	?>

	<?php if ( $content['opinion'] !== array() ) : ?>
		<?php get_template_part( 'template-parts/blocks/opinion-strip', null, array( 'posts' => $content['opinion'] ) ); ?>
	<?php endif; ?>

	<?php get_template_part( 'template-parts/blocks/promo-banner' ); ?>

	<div class="triptych">
		<?php
		get_template_part(
			'template-parts/blocks/category-block',
			null,
			array(
				'heading' => __( 'Judiciales', 'dnorte-theme' ),
				'mode'    => 'dense',
				'posts'   => $content['judiciales'],
			)
		);
		?>

		<?php
		get_template_part(
			'template-parts/blocks/editorial-column',
			null,
			array(
				'editorial'      => $content['editorial'],
				'edicionImpresa' => $content['edicionImpresa'],
			)
		);
		?>

		<?php get_template_part( 'template-parts/blocks/most-read', null, array( 'posts' => $content['mostRead'] ) ); ?>
	</div>

	<?php
	get_template_part(
		'template-parts/blocks/news-grid',
		null,
		array(
			'posts'       => $content['newsGrid'],
			'excludedIds' => $content['newsGridExcluded'],
		)
	);
	?>

	<?php if ( $content['hero'] === null ) : ?>
		<p><?php esc_html_e( 'Todavía no hay artículos publicados.', 'dnorte-theme' ); ?></p>
	<?php endif; ?>
</main>

<?php
get_footer();
