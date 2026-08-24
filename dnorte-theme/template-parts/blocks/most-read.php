<?php
/**
 * "Lo más leído" — ranking real por vistas (HomeContentProvider::content()
 * ['mostRead'], Analytics\Pageviews\PageviewRepository), con números grandes y
 * tres ventanas de tiempo (24 horas/7 días/30 días). Las tres listas ya vienen
 * en el HTML — el filtro solo muestra/oculta con JS (initMostReadFilter(),
 * assets/js/app.js), sin volver a pedir nada al servidor. Deliberadamente más
 * abajo en la portada, después de "Más noticias": no debe competir con el hero
 * ni aparecer como si fuera la noticia principal del día.
 *
 * @package DNorteTheme
 * @var array{mostRead: array{'24h': list<WP_Post>, '7d': list<WP_Post>, '30d': list<WP_Post>}} $args
 */

declare(strict_types=1);

/** @var array{'24h': list<WP_Post>, '7d': list<WP_Post>, '30d': list<WP_Post>} $windows */
$windows = $args['mostRead'];

if ( $windows['24h'] === array() && $windows['7d'] === array() && $windows['30d'] === array() ) {
	return;
}

$windowTabs = array(
	'24h' => __( '24 horas', 'dnorte-theme' ),
	'7d'  => __( '7 días', 'dnorte-theme' ),
	'30d' => __( '30 días', 'dnorte-theme' ),
);
?>
<section class="most-read-section" aria-label="<?php esc_attr_e( 'Lo más leído', 'dnorte-theme' ); ?>">
	<h2 class="section-heading"><?php esc_html_e( 'Lo más leído', 'dnorte-theme' ); ?></h2>

	<div class="most-read-tabs" role="tablist" data-most-read-tabs>
		<?php $first = true; ?>
		<?php foreach ( $windowTabs as $windowKey => $label ) : ?>
			<button
				type="button"
				role="tab"
				class="most-read-tabs__button<?php echo $first ? ' is-active' : ''; ?>"
				aria-selected="<?php echo $first ? 'true' : 'false'; ?>"
				data-most-read-tab="<?php echo esc_attr( $windowKey ); ?>"
			>
				<?php echo esc_html( $label ); ?>
			</button>
			<?php $first = false; ?>
		<?php endforeach; ?>
	</div>

	<?php $first = true; ?>
	<?php foreach ( $windowTabs as $windowKey => $label ) : ?>
		<ol class="most-read" data-most-read-panel="<?php echo esc_attr( $windowKey ); ?>" <?php echo $first ? '' : 'hidden'; ?>>
			<?php foreach ( $windows[ $windowKey ] as $readPost ) : ?>
				<li>
					<span class="most-read__rank" aria-hidden="true"></span>
					<?php if ( has_post_thumbnail( $readPost ) ) : ?>
						<span class="most-read__media">
							<?php echo get_the_post_thumbnail( $readPost, 'dnorte-thumb', array( 'alt' => '' ) ); ?>
						</span>
					<?php endif; ?>
					<a class="most-read__title" href="<?php echo esc_url( get_permalink( $readPost ) ); ?>">
						<?php echo esc_html( get_the_title( $readPost ) ); ?>
					</a>
				</li>
			<?php endforeach; ?>
			<?php if ( $windows[ $windowKey ] === array() ) : ?>
				<li class="most-read__empty"><?php esc_html_e( 'Todavía no hay datos suficientes en este periodo.', 'dnorte-theme' ); ?></li>
			<?php endif; ?>
		</ol>
		<?php $first = false; ?>
	<?php endforeach; ?>
</section>
