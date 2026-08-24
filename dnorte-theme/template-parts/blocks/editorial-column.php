<?php
/**
 * Columna "Editorial" + "Edición impresa" — el último artículo de cada una de
 * esas dos categorías (edicion-impresa reutiliza el mismo patrón que el resto
 * de bloques de categoría: la imagen destacada del post hace de "portada" del
 * día, sin ningún sistema nuevo de subida de PDF/portada).
 *
 * @package DNorteTheme
 * @var array{editorial: WP_Post|null, edicionImpresa: WP_Post|null} $args
 */

declare(strict_types=1);

/** @var WP_Post|null $editorialPost */
$editorialPost = $args['editorial'];
/** @var WP_Post|null $impresaPost */
$impresaPost = $args['edicionImpresa'];

if ( $editorialPost === null && $impresaPost === null ) {
	return;
}
?>
<div class="editorial-column">
	<h2 class="section-heading"><?php esc_html_e( 'Editorial', 'dnorte-theme' ); ?></h2>

	<?php if ( $editorialPost !== null ) : ?>
		<a class="editorial-column__post" href="<?php echo esc_url( get_permalink( $editorialPost ) ); ?>">
			<?php if ( has_post_thumbnail( $editorialPost ) ) : ?>
				<span class="editorial-column__media">
					<?php echo get_the_post_thumbnail( $editorialPost, 'dnorte-card', array( 'alt' => '' ) ); ?>
				</span>
			<?php endif; ?>
			<span class="editorial-column__title"><?php echo esc_html( get_the_title( $editorialPost ) ); ?></span>
			<span class="editorial-column__meta">
				<?php
				printf(
					/* translators: 1: nombre del sitio, 2: fecha de publicación. */
					esc_html__( 'Por %1$s · %2$s', 'dnorte-theme' ),
					esc_html( get_bloginfo( 'name' ) ),
					esc_html( get_the_date( '', $editorialPost ) )
				);
				?>
			</span>
		</a>
	<?php endif; ?>

	<?php if ( $impresaPost !== null && has_post_thumbnail( $impresaPost ) ) : ?>
		<a class="editorial-column__print" href="<?php echo esc_url( get_permalink( $impresaPost ) ); ?>">
			<span class="editorial-column__print-cover">
				<?php echo get_the_post_thumbnail( $impresaPost, 'dnorte-thumb', array( 'alt' => '' ) ); ?>
			</span>
			<span>
				<span class="kicker" data-category="edicion-impresa"><?php esc_html_e( 'Edición impresa', 'dnorte-theme' ); ?></span>
				<span class="editorial-column__print-caption"><?php echo esc_html( get_the_title( $impresaPost ) ); ?></span>
			</span>
		</a>
	<?php endif; ?>
</div>
