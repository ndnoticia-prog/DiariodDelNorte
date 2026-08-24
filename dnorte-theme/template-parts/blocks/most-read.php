<?php
/**
 * "Lo más leído" — ranking real por vistas (HomeContentProvider::mostRead(),
 * Analytics\Pageviews\PageviewRepository), numerado 01, 02... con CSS counter,
 * no texto fijo por elemento.
 *
 * @package DNorteTheme
 * @var array{posts: list<WP_Post>} $args
 */

declare(strict_types=1);

/** @var list<WP_Post> $mostReadPosts */
$mostReadPosts = $args['posts'];

if ( $mostReadPosts === array() ) {
	return;
}
?>
<div class="most-read-column">
	<h2 class="section-heading"><?php esc_html_e( 'Lo más leído', 'dnorte-theme' ); ?></h2>
	<ol class="most-read">
		<?php foreach ( $mostReadPosts as $mostReadPost ) : ?>
			<li>
				<span class="most-read__rank" aria-hidden="true"></span>
				<?php if ( has_post_thumbnail( $mostReadPost ) ) : ?>
					<span class="most-read__media">
						<?php echo get_the_post_thumbnail( $mostReadPost, 'dnorte-thumb', array( 'alt' => '' ) ); ?>
					</span>
				<?php endif; ?>
				<a class="most-read__title" href="<?php echo esc_url( get_permalink( $mostReadPost ) ); ?>">
					<?php echo esc_html( get_the_title( $mostReadPost ) ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ol>
</div>
