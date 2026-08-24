<?php
/**
 * Edición Impresa — miniatura grande de la portada del día (la imagen
 * destacada del último post de la categoría "edicion-impresa", mismo patrón
 * de categoría que el resto de bloques, sin sistema nuevo de subida) más dos
 * acciones: "Ver edición digital" (el propio artículo) y "Descargar PDF" (el
 * primer PDF adjunto al post en la Biblioteca de medios — si no hay ninguno,
 * el botón simplemente no se imprime, nunca un enlace roto). Deliberadamente
 * después de "Lo más leído" en la portada: no compite con las noticias
 * principales.
 *
 * @package DNorteTheme
 * @var array{post: WP_Post|null, pdfUrl: string} $args
 */

declare(strict_types=1);

/** @var WP_Post|null $edition */
$edition = $args['post'];

if ( $edition === null ) {
	return;
}

/** @var string $pdfUrl */
$pdfUrl = $args['pdfUrl'];
?>
<section class="print-edition">
	<div class="print-edition__inner">
		<?php if ( has_post_thumbnail( $edition ) ) : ?>
			<a class="print-edition__cover" href="<?php echo esc_url( get_permalink( $edition ) ); ?>">
				<?php
				echo get_the_post_thumbnail(
					$edition,
					'dnorte-featured',
					array(
						'loading' => 'lazy',
						'alt'     => get_the_title( $edition ),
					)
				);
				?>
			</a>
		<?php endif; ?>
		<div class="print-edition__body">
			<span class="kicker" data-category="edicion-impresa"><?php esc_html_e( 'Edición impresa', 'dnorte-theme' ); ?></span>
			<h2 class="print-edition__title"><?php bloginfo( 'name' ); ?></h2>
			<p class="print-edition__caption"><?php echo esc_html( get_the_title( $edition ) ); ?></p>
			<div class="print-edition__actions">
				<a class="print-edition__cta print-edition__cta--primary" href="<?php echo esc_url( get_permalink( $edition ) ); ?>">
					<?php esc_html_e( 'Ver edición digital', 'dnorte-theme' ); ?>
				</a>
				<?php if ( $pdfUrl !== '' ) : ?>
					<a class="print-edition__cta print-edition__cta--secondary" href="<?php echo esc_url( $pdfUrl ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Descargar PDF', 'dnorte-theme' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
