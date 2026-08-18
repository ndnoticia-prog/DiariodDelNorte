<?php
/**
 * Plantilla de artículo individual.
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

get_header();

while ( have_posts() ) :
	the_post();
	$categories = get_the_category();
	?>
	<main id="contenido-principal" class="site-main site-main--article" role="main">
		<?php get_template_part( 'template-parts/breadcrumbs' ); ?>

		<article <?php post_class( 'single-article' ); ?>>
			<header class="single-article__header">
				<?php if ( $categories !== array() ) : ?>
					<a class="kicker" href="<?php echo esc_url( (string) get_category_link( $categories[0] ) ); ?>">
						<?php echo esc_html( $categories[0]->name ); ?>
					</a>
				<?php endif; ?>

				<h1 class="single-article__title"><?php the_title(); ?></h1>

				<p class="single-article__meta">
					<?php
					printf(
						/* translators: 1: nombre del autor, 2: fecha de publicación */
						esc_html__( 'Por %1$s · %2$s', 'dnorte-theme' ),
						esc_html( get_the_author() ),
						'<time datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( get_the_date() ) . '</time>'
					);
					?>
				</p>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="single-article__media">
					<?php
					the_post_thumbnail(
						'large',
						array(
							'loading'       => 'eager',
							'fetchpriority' => 'high',
						)
					);
					?>
				</div>
			<?php endif; ?>

			<div class="single-article__content">
				<?php the_content(); ?>
			</div>
		</article>

		<?php if ( comments_open() || get_comments_number() ) : ?>
			<?php comments_template(); ?>
		<?php endif; ?>
	</main>
	<?php
endwhile;

get_footer();
