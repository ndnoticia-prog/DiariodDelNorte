<?php
/**
 * "Lo último": ticker + artículo destacado + tira de miniaturas, todos del
 * mismo grupo de artículos recientes (HomeContentProvider::content()['hero'] +
 * ['heroThumbs']). Sin JS cada miniatura/flecha es un <a> normal a su propio
 * artículo (funciona igual, solo sin la animación); con JS
 * (initHeroCarousel(), assets/js/app.js) clicar una miniatura o una flecha
 * además intercambia la imagen/kicker/título del hero visible, leyendo los
 * data-* que ya trae cada miniatura — nunca vuelve a pedir nada al servidor.
 *
 * @package DNorteTheme
 * @var array{hero: WP_Post, thumbs: list<WP_Post>, aside: list<WP_Post>} $args
 */

declare(strict_types=1);

/** @var WP_Post $heroPost */
$heroPost = $args['hero'];
/** @var list<WP_Post> $thumbs */
$thumbs = $args['thumbs'];

$heroCategories = get_the_category( $heroPost->ID );
$heroCategory   = $heroCategories[0] ?? null;
?>
<section class="hero-carousel" aria-label="<?php esc_attr_e( 'Lo último', 'dnorte-theme' ); ?>">
	<div class="hero-carousel__ticker">
		<span class="hero-carousel__ticker-label"><?php esc_html_e( 'Lo último', 'dnorte-theme' ); ?></span>
		<p class="hero-carousel__ticker-title" data-hero-ticker>
			<a href="<?php echo esc_url( get_permalink( $heroPost ) ); ?>"><?php echo esc_html( get_the_title( $heroPost ) ); ?></a>
		</p>
		<?php if ( $thumbs !== array() ) : ?>
			<div class="hero-carousel__ticker-nav">
				<button type="button" class="hero-carousel__ticker-btn" data-hero-prev aria-label="<?php esc_attr_e( 'Artículo anterior', 'dnorte-theme' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" /></svg>
				</button>
				<button type="button" class="hero-carousel__ticker-btn" data-hero-next aria-label="<?php esc_attr_e( 'Artículo siguiente', 'dnorte-theme' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" /></svg>
				</button>
			</div>
		<?php endif; ?>
	</div>

	<div class="hero-carousel__grid">
		<div class="hero-carousel__main">
			<a class="hero-carousel__link" href="<?php echo esc_url( get_permalink( $heroPost ) ); ?>" data-hero-main>
				<?php if ( has_post_thumbnail( $heroPost ) ) : ?>
					<div class="hero-carousel__media">
						<?php if ( $heroCategory !== null ) : ?>
							<span class="kicker kicker--pill" data-category="<?php echo esc_attr( $heroCategory->slug ); ?>" data-hero-kicker><?php echo esc_html( $heroCategory->name ); ?></span>
						<?php endif; ?>
						<?php
						echo get_the_post_thumbnail(
							$heroPost,
							'dnorte-featured',
							array(
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'alt'           => get_the_title( $heroPost ),
								'data-hero-img' => '1',
							)
						);
						?>
					</div>
				<?php endif; ?>
				<h1 class="hero-carousel__title" data-hero-title><?php echo esc_html( get_the_title( $heroPost ) ); ?></h1>
			</a>

			<?php if ( $thumbs !== array() ) : ?>
				<ul class="hero-carousel__thumbs">
					<li class="hero-carousel__thumb is-active">
						<a
							href="<?php echo esc_url( get_permalink( $heroPost ) ); ?>"
							data-hero-id="<?php echo esc_attr( (string) $heroPost->ID ); ?>"
							data-hero-title="<?php echo esc_attr( get_the_title( $heroPost ) ); ?>"
							data-hero-category="<?php echo esc_attr( $heroCategory->name ?? '' ); ?>"
							data-hero-category-slug="<?php echo esc_attr( $heroCategory->slug ?? '' ); ?>"
							data-hero-image="<?php echo esc_url( (string) get_the_post_thumbnail_url( $heroPost, 'dnorte-featured' ) ); ?>"
						>
							<?php echo get_the_post_thumbnail( $heroPost, 'dnorte-thumb', array( 'alt' => '' ) ); ?>
						</a>
					</li>
					<?php foreach ( $thumbs as $thumbPost ) : ?>
						<?php $thumbCategory = get_the_category( $thumbPost->ID )[0] ?? null; ?>
						<li class="hero-carousel__thumb">
							<a
								href="<?php echo esc_url( get_permalink( $thumbPost ) ); ?>"
								data-hero-id="<?php echo esc_attr( (string) $thumbPost->ID ); ?>"
								data-hero-title="<?php echo esc_attr( get_the_title( $thumbPost ) ); ?>"
								data-hero-category="<?php echo esc_attr( $thumbCategory->name ?? '' ); ?>"
								data-hero-category-slug="<?php echo esc_attr( $thumbCategory->slug ?? '' ); ?>"
								data-hero-image="<?php echo esc_url( (string) get_the_post_thumbnail_url( $thumbPost, 'dnorte-featured' ) ); ?>"
							>
								<?php echo get_the_post_thumbnail( $thumbPost, 'dnorte-thumb', array( 'alt' => '' ) ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<?php if ( $args['aside'] !== array() ) : ?>
			<div class="hero-carousel__aside">
				<?php foreach ( $args['aside'] as $asidePost ) : ?>
					<?php get_template_part( 'template-parts/blocks/mini-card', null, array( 'post' => $asidePost ) ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
