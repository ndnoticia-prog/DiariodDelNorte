<?php
/**
 * Tira horizontal de columnistas — foto de autor (avatar de WordPress),
 * etiqueta "Opinión" y titular.
 *
 * @package DNorteTheme
 * @var array{posts: list<WP_Post>} $args
 */

declare(strict_types=1);

/** @var list<WP_Post> $opinionPosts */
$opinionPosts = $args['posts'];
?>
<section class="opinion-strip">
	<h2 class="section-heading"><?php esc_html_e( 'Opinión', 'dnorte-theme' ); ?></h2>
	<div class="opinion-strip__track">
		<?php foreach ( $opinionPosts as $opinionPost ) : ?>
			<a class="opinion-strip__card" href="<?php echo esc_url( get_permalink( $opinionPost ) ); ?>">
				<span class="opinion-strip__avatar">
					<?php echo get_avatar( (int) $opinionPost->post_author, 128 ); ?>
				</span>
				<span class="kicker" data-category="opinion"><?php esc_html_e( 'Opinión', 'dnorte-theme' ); ?></span>
				<span class="opinion-strip__title"><?php echo esc_html( get_the_title( $opinionPost ) ); ?></span>
				<time class="entry-date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $opinionPost ) ); ?>">
					<?php echo esc_html( get_the_date( '', $opinionPost ) ); ?>
				</time>
			</a>
		<?php endforeach; ?>
	</div>
</section>
