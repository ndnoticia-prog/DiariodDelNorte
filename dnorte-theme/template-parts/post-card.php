<?php
/**
 * Tarjeta de artículo reutilizable — cuadrícula de portada y archivos.
 *
 * @package DNorteTheme
 * @var array{post: WP_Post} $args
 */

declare(strict_types=1);

/** @var WP_Post $cardPost */
$cardPost   = $args['post'];
$categories = get_the_category( $cardPost->ID );
?>
<article <?php post_class( 'post-card', $cardPost ); ?>>
	<a class="post-card__link" href="<?php echo esc_url( get_permalink( $cardPost ) ); ?>">
		<?php if ( has_post_thumbnail( $cardPost ) ) : ?>
			<div class="post-card__media">
				<?php
				echo get_the_post_thumbnail(
					$cardPost,
					'medium',
					array(
						'loading' => 'lazy',
						'alt'     => get_the_title( $cardPost ),
					)
				);
				?>
			</div>
		<?php endif; ?>
		<div class="post-card__body">
			<?php if ( $categories !== array() ) : ?>
				<span class="kicker"><?php echo esc_html( $categories[0]->name ); ?></span>
			<?php endif; ?>
			<h3 class="post-card__title"><?php echo esc_html( get_the_title( $cardPost ) ); ?></h3>
		</div>
	</a>
</article>
