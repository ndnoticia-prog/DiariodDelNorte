<?php
/**
 * Bloque de sección por categoría. Dos modos:
 * - 'featured-list' (La Guajira): unas pocas tarjetas grandes (post-card)
 *   arriba + una lista secundaria más compacta (mini-card) debajo.
 * - 'dense' (Judiciales): una única cuadrícula tupida de tarjetas, sin
 *   segundo nivel.
 *
 * @package DNorteTheme
 * @var array{
 *     heading: string,
 *     mode: 'featured-list'|'dense',
 *     featured?: list<WP_Post>,
 *     list?: list<WP_Post>,
 *     posts?: list<WP_Post>
 * } $args
 */

declare(strict_types=1);

$heading   = $args['heading'];
$blockMode = $args['mode'];
?>
<section class="category-block">
	<h2 class="section-heading"><?php echo esc_html( $heading ); ?></h2>

	<?php if ( $blockMode === 'featured-list' ) : ?>
		<?php
		/** @var list<WP_Post> $featured */
		$featured = $args['featured'] ?? array();
		/** @var list<WP_Post> $list */
		$list = $args['list'] ?? array();
		?>
		<?php if ( $featured !== array() ) : ?>
			<div class="category-block__featured">
				<?php foreach ( $featured as $featuredPost ) : ?>
					<?php get_template_part( 'template-parts/post-card', null, array( 'post' => $featuredPost ) ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $list !== array() ) : ?>
			<div class="category-block__list">
				<?php foreach ( $list as $listPost ) : ?>
					<?php get_template_part( 'template-parts/blocks/mini-card', null, array( 'post' => $listPost ) ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<?php
		/** @var list<WP_Post> $densePosts */
		$densePosts = $args['posts'] ?? array();
		?>
		<?php if ( $densePosts !== array() ) : ?>
			<div class="category-block__dense">
				<?php foreach ( $densePosts as $densePost ) : ?>
					<?php get_template_part( 'template-parts/post-card', null, array( 'post' => $densePost ) ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</section>
