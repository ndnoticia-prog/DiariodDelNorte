<?php
/**
 * Portada del sitio (v0.1.0-alpha.18: evolución editorial completa, hero de
 * gran formato + jerarquía visual explícita): Hero → La Guajira → Judiciales →
 * Opinión → Más noticias → Lo más leído → Edición Impresa → Newsletter.
 * "Lo más leído" y "Edición impresa" deliberadamente después de "Más
 * noticias" — no deben competir con el hero ni con las noticias principales.
 * Todo el contenido viene de DNorteTheme\Content\HomeContentProvider — esta
 * plantilla solo decide qué bloque imprimir con qué datos, sin ninguna
 * consulta propia.
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
			'template-parts/blocks/hero',
			null,
			array(
				'hero'      => $content['hero'],
				'secondary' => $content['heroSecondary'],
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

	<?php if ( $content['opinion'] !== array() ) : ?>
		<?php get_template_part( 'template-parts/blocks/opinion-strip', null, array( 'posts' => $content['opinion'] ) ); ?>
	<?php endif; ?>

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

	<?php get_template_part( 'template-parts/blocks/most-read', null, array( 'mostRead' => $content['mostRead'] ) ); ?>

	<?php
	get_template_part(
		'template-parts/blocks/print-edition',
		null,
		array(
			'post'   => $content['edicionImpresa'],
			'pdfUrl' => $content['edicionImpresaPdfUrl'],
		)
	);
	?>

	<?php get_template_part( 'template-parts/blocks/newsletter' ); ?>

	<?php if ( $content['hero'] === null ) : ?>
		<p><?php esc_html_e( 'Todavía no hay artículos publicados.', 'dnorte-theme' ); ?></p>
	<?php endif; ?>
</main>

<?php
get_footer();
