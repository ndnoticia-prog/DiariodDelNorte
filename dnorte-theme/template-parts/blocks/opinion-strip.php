<?php
/**
 * Opinión con identidad propia de columnista — no otra parrilla de noticias
 * más: retrato circular, nombre, cargo/nombre de columna (biografía del
 * autor en WordPress — el propio equipo decide si ahí pone un cargo tipo
 * "DIRECTOR" o el nombre de su columna tipo "ENTRE EL RÍO Y EL MAR", ambos
 * caben en el mismo campo), título del artículo y un breve extracto.
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
			<?php
			$authorId   = (int) $opinionPost->post_author;
			$authorName = get_the_author_meta( 'display_name', $authorId );
			$authorRole = trim( wp_strip_all_tags( (string) get_the_author_meta( 'description', $authorId ) ) );
			$excerpt    = wp_strip_all_tags( get_the_excerpt( $opinionPost ) );
			?>
			<a class="opinion-strip__card" href="<?php echo esc_url( get_permalink( $opinionPost ) ); ?>">
				<span class="opinion-strip__avatar">
					<?php echo get_avatar( $authorId, 128 ); ?>
				</span>
				<span class="opinion-strip__author"><?php echo esc_html( $authorName ); ?></span>
				<?php if ( $authorRole !== '' ) : ?>
					<span class="opinion-strip__role"><?php echo esc_html( mb_strtoupper( $authorRole, 'UTF-8' ) ); ?></span>
				<?php endif; ?>
				<span class="opinion-strip__title"><?php echo esc_html( get_the_title( $opinionPost ) ); ?></span>
				<?php if ( $excerpt !== '' ) : ?>
					<span class="opinion-strip__excerpt"><?php echo esc_html( wp_trim_words( $excerpt, 18 ) ); ?></span>
				<?php endif; ?>
				<time class="entry-date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $opinionPost ) ); ?>">
					<?php echo esc_html( get_the_date( '', $opinionPost ) ); ?>
				</time>
			</a>
		<?php endforeach; ?>
	</div>
</section>
