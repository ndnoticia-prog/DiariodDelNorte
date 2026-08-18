<?php
/**
 * Bloque de última hora — lista corta de titulares recientes.
 *
 * @package DNorteTheme
 * @var array{posts: list<WP_Post>} $args
 */

declare(strict_types=1);

/** @var list<WP_Post> $breakingPosts */
$breakingPosts = $args['posts'];
?>
<section class="block-breaking" aria-label="<?php esc_attr_e( 'Última hora', 'dnorte-theme' ); ?>">
	<span class="block-breaking__label"><?php esc_html_e( 'Última hora', 'dnorte-theme' ); ?></span>
	<ul class="block-breaking__list">
		<?php foreach ( $breakingPosts as $breakingPost ) : ?>
			<li>
				<a href="<?php echo esc_url( get_permalink( $breakingPost ) ); ?>"><?php echo esc_html( get_the_title( $breakingPost ) ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
