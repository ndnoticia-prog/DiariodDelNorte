<?php
/**
 * Bloque de cuadrícula — más noticias recientes.
 *
 * @package DNorteTheme
 * @var array{posts: list<WP_Post>} $args
 */

declare(strict_types=1);

/** @var list<WP_Post> $latestPosts */
$latestPosts = $args['posts'];
?>
<section class="block-latest">
	<h2 class="block-latest__heading"><?php esc_html_e( 'Más noticias', 'dnorte-theme' ); ?></h2>
	<div class="post-grid">
		<?php foreach ( $latestPosts as $latestPost ) : ?>
			<?php get_template_part( 'template-parts/post-card', null, array( 'post' => $latestPost ) ); ?>
		<?php endforeach; ?>
	</div>
</section>
