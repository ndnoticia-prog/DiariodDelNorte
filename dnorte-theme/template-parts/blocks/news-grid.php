<?php
/**
 * "Más noticias" — cuadrícula final + "Cargar más". El botón pide más
 * artículos a la API REST nativa de WordPress (wp/v2/posts, sin endpoint
 * propio) vía JS (initLoadMore(), assets/js/app.js) y los añade al final de la
 * cuadrícula sin recargar la página; sin JS, el botón simplemente no hace nada
 * pero el resto de la portada funciona igual — degradación aceptada a
 * propósito, ver el mismo criterio en searchform.php (GET normal, sin JS).
 *
 * data-excluded-ids lleva los ids ya mostrados en el resto de la portada
 * (mismo criterio de HomeContentProvider: solo evita repetir lo que ya se ve
 * en "Lo último", no todo lo mostrado en el resto de bloques).
 *
 * @package DNorteTheme
 * @var array{posts: list<WP_Post>, excludedIds: list<int>} $args
 */

declare(strict_types=1);

/** @var list<WP_Post> $newsPosts */
$newsPosts = $args['posts'];

if ( $newsPosts === array() ) {
	return;
}

/** @var list<int> $excludedIds */
$excludedIds = $args['excludedIds'];
?>
<section class="news-grid" aria-label="<?php esc_attr_e( 'Más noticias', 'dnorte-theme' ); ?>">
	<h2 class="section-heading"><?php esc_html_e( 'Más noticias', 'dnorte-theme' ); ?></h2>
	<div class="post-grid" data-news-grid data-excluded-ids="<?php echo esc_attr( implode( ',', $excludedIds ) ); ?>">
		<?php foreach ( $newsPosts as $newsPost ) : ?>
			<?php get_template_part( 'template-parts/post-card', null, array( 'post' => $newsPost ) ); ?>
		<?php endforeach; ?>
	</div>
	<div class="load-more">
		<button type="button" class="load-more__button" data-load-more>
			<?php esc_html_e( 'Cargar más', 'dnorte-theme' ); ?>
		</button>
	</div>
</section>
