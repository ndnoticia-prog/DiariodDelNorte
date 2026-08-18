<?php
/**
 * Plantilla de comentarios. Sin esta plantilla, WordPress emite un aviso de
 * deprecación en cada artículo (`comments_template()` cae al compat layer interno,
 * obsoleto desde WP 3.0) — encontrado en la primera verificación real en navegador,
 * no por revisión de código. Mismo tipo de hallazgo ya documentado en el handoff de
 * ND Platform.
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area">

	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title">
			<?php
			$commentCount = get_comments_number();
			printf(
				/* translators: %s: número de comentarios */
				esc_html( _n( '%s comentario', '%s comentarios', $commentCount, 'dnorte-theme' ) ),
				esc_html( number_format_i18n( $commentCount ) )
			);
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
				)
			);
			?>
		</ol>

		<?php the_comments_pagination(); ?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() !== 0 ) : ?>
		<p class="comments-closed"><?php esc_html_e( 'Los comentarios están cerrados.', 'dnorte-theme' ); ?></p>
	<?php endif; ?>

	<?php comment_form(); ?>

</div>
