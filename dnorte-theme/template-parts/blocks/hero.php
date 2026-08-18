<?php
/**
 * Bloque hero de portada — el artículo más reciente.
 *
 * @package DNorteTheme
 * @var array{post: WP_Post} $args
 */

declare(strict_types=1);

/** @var WP_Post $heroPost */
$heroPost   = $args['post'];
$categories = get_the_category( $heroPost->ID );
?>
<section class="block-hero">
	<a class="block-hero__link" href="<?php echo esc_url( get_permalink( $heroPost ) ); ?>">
		<?php if ( has_post_thumbnail( $heroPost ) ) : ?>
			<div class="block-hero__media">
				<?php
				echo get_the_post_thumbnail(
					$heroPost,
					'dnorte-featured',
					array(
						'loading'       => 'eager',
						'fetchpriority' => 'high',
						'alt'           => get_the_title( $heroPost ),
					)
				);
				?>
			</div>
		<?php endif; ?>
		<div class="block-hero__body">
			<?php if ( $categories !== array() ) : ?>
				<span class="kicker"><?php echo esc_html( $categories[0]->name ); ?></span>
			<?php endif; ?>
			<h1 class="block-hero__title"><?php echo esc_html( get_the_title( $heroPost ) ); ?></h1>
			<p class="block-hero__excerpt"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt( $heroPost ) ) ); ?></p>
		</div>
	</a>
</section>
