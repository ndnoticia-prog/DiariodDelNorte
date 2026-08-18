<?php
/**
 * Plantilla de archivo: cubre categoría/etiqueta/autor/fecha (fallback automático de
 * WordPress para esos cuatro contextos) — no hace falta duplicarla en
 * category.php/tag.php/author.php.
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

get_header();

$queried = get_queried_object();
// El nombre del término directo, no get_the_archive_title(): WordPress core antepone
// "Category: "/"Tag: " al título (correcto para el <title> SEO — ver
// Seo\Context\SeoContextResolver en dnorte-core — pero no para un <h1> visible).
// Mismo criterio ya aplicado en Seo\Breadcrumbs\BreadcrumbBuilder.
$archiveTitle = $queried instanceof WP_Term ? $queried->name : get_the_archive_title();
?>

<main id="contenido-principal" class="site-main" role="main">
	<?php get_template_part( 'template-parts/breadcrumbs' ); ?>

	<header class="archive-header">
		<h1 class="archive-header__title"><?php echo esc_html( wp_strip_all_tags( $archiveTitle ) ); ?></h1>
		<?php the_archive_description( '<div class="archive-header__description">', '</div>' ); ?>
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
		<p><?php esc_html_e( 'No se encontró contenido.', 'dnorte-theme' ); ?></p>
	<?php endif; ?>
</main>

<?php
get_footer();
