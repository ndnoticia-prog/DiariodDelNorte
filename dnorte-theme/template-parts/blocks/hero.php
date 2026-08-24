<?php
/**
 * Hero de portada — protagonista, ancho completo del contenedor, con el texto
 * (etiqueta/titular/bajada/hora) superpuesto a la fotografía sobre un degradado
 * oscuro, más dos noticias secundarias a un lado (en escritorio) o debajo (en
 * móvil). Una sola noticia principal, nunca un carrusel de miniaturas — la
 * jerarquía editorial pedida es "una historia manda", no varias compitiendo.
 *
 * @package DNorteTheme
 * @var array{hero: WP_Post, secondary: list<WP_Post>} $args
 */

declare(strict_types=1);

use DNorteTheme\Support\RelativeDate;

/** @var WP_Post $heroPost */
$heroPost = $args['hero'];
/** @var list<WP_Post> $secondary */
$secondary = $args['secondary'];

$heroCategory = get_the_category( $heroPost->ID )[0] ?? null;
?>
<section class="hero">
	<a class="hero__main" href="<?php echo esc_url( get_permalink( $heroPost ) ); ?>">
		<div class="hero__media">
			<?php if ( has_post_thumbnail( $heroPost ) ) : ?>
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
			<?php endif; ?>
			<div class="hero__scrim"></div>
			<div class="hero__text">
				<?php if ( $heroCategory !== null ) : ?>
					<span class="kicker kicker--pill" data-category="<?php echo esc_attr( $heroCategory->slug ); ?>"><?php echo esc_html( $heroCategory->name ); ?></span>
				<?php endif; ?>
				<h1 class="hero__title"><?php echo esc_html( get_the_title( $heroPost ) ); ?></h1>
				<?php if ( get_the_excerpt( $heroPost ) !== '' ) : ?>
					<p class="hero__dek"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt( $heroPost ) ) ); ?></p>
				<?php endif; ?>
				<p class="hero__meta"><?php echo esc_html( mb_strtoupper( RelativeDate::forPost( $heroPost ), 'UTF-8' ) ); ?></p>
			</div>
		</div>
	</a>

	<?php if ( $secondary !== array() ) : ?>
		<div class="hero__secondary">
			<?php foreach ( $secondary as $secondaryPost ) : ?>
				<?php $secondaryCategory = get_the_category( $secondaryPost->ID )[0] ?? null; ?>
				<a class="hero__secondary-item" href="<?php echo esc_url( get_permalink( $secondaryPost ) ); ?>">
					<?php if ( has_post_thumbnail( $secondaryPost ) ) : ?>
						<div class="hero__secondary-media">
							<?php
							echo get_the_post_thumbnail(
								$secondaryPost,
								'dnorte-card',
								array(
									'alt'     => '',
									'loading' => 'lazy',
								)
							);
							?>
						</div>
					<?php endif; ?>
					<div class="hero__secondary-body">
						<?php if ( $secondaryCategory !== null ) : ?>
							<span class="kicker" data-category="<?php echo esc_attr( $secondaryCategory->slug ); ?>"><?php echo esc_html( $secondaryCategory->name ); ?></span>
						<?php endif; ?>
						<h3 class="hero__secondary-title"><?php echo esc_html( get_the_title( $secondaryPost ) ); ?></h3>
						<time class="entry-date"><?php echo esc_html( RelativeDate::forPost( $secondaryPost ) ); ?></time>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
