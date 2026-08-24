<?php
/**
 * Tarjeta compacta reutilizable — columna junto al hero de "Lo último" y lista
 * secundaria de un bloque de categoría (ej. La Guajira).
 *
 * @package DNorteTheme
 * @var array{post: WP_Post, stacked?: bool} $args
 */

declare(strict_types=1);

use DNorteTheme\Support\RelativeDate;

/** @var WP_Post $miniPost */
$miniPost   = $args['post'];
$stacked    = $args['stacked'] ?? false;
$categories = get_the_category( $miniPost->ID );
$category   = $categories[0] ?? null;
?>
<a class="mini-card<?php echo $stacked ? ' mini-card--stacked' : ''; ?>" href="<?php echo esc_url( get_permalink( $miniPost ) ); ?>">
	<?php if ( has_post_thumbnail( $miniPost ) ) : ?>
		<span class="mini-card__media">
			<?php
			echo get_the_post_thumbnail(
				$miniPost,
				'dnorte-thumb',
				array(
					'loading' => 'lazy',
					'alt'     => '',
				)
			);
			?>
		</span>
	<?php endif; ?>
	<span>
		<?php if ( $category !== null ) : ?>
			<span class="kicker" data-category="<?php echo esc_attr( $category->slug ); ?>"><?php echo esc_html( $category->name ); ?></span>
		<?php endif; ?>
		<span class="mini-card__title"><?php echo esc_html( get_the_title( $miniPost ) ); ?></span>
		<time class="entry-date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $miniPost ) ); ?>">
			<?php echo esc_html( RelativeDate::forPost( $miniPost ) ); ?>
		</time>
	</span>
</a>
