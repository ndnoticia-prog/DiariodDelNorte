<?php
/**
 * Página de resultados de búsqueda. La consulta principal ya llega con ranking por
 * relevancia real (MATCH ... AGAINST) en vez del orden por fecha de WordPress core
 * — ver Search\SearchQueryModifier en dnorte-core, enganchado a posts_search/
 * posts_orderby de forma transparente para cualquier WP_Query de búsqueda.
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

get_header();
?>

<main id="contenido-principal" class="site-main" role="main">
	<header class="archive-header">
		<h1 class="archive-header__title">
			<?php
			printf(
				/* translators: %s: término buscado. */
				esc_html__( 'Resultados para: %s', 'dnorte-theme' ),
				'<span>' . esc_html( get_search_query() ) . '</span>'
			);
			?>
		</h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="post-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/post-card', null, array( 'post' => get_post() ) );
			endwhile;
			?>
		</div>

		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No se encontraron artículos con ese término.', 'dnorte-theme' ); ?></p>
	<?php endif; ?>
</main>

<?php
get_footer();
