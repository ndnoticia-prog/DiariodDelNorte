<?php
/**
 * Banner promocional de WhatsApp — no es ninguno de los cinco espacios de
 * publicidad (ver docs/Architecture.md, "Publicidad propia"), así que no pasa
 * por Ads\AdSlotRenderer: es un ajuste propio del tema
 * (ThemeServiceProvider::registerCustomizer()). Sin número configurado en el
 * Personalizador, no imprime nada — nunca un número de contacto de ejemplo
 * simulando ser real.
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

$whatsappNumber = get_theme_mod( 'dnorte_whatsapp_number', '' );

if ( ! is_string( $whatsappNumber ) || trim( $whatsappNumber ) === '' ) {
	return;
}

$digitsOnly = preg_replace( '/\D+/', '', $whatsappNumber );

if ( $digitsOnly === null || $digitsOnly === '' ) {
	return;
}
?>
<div class="promo-banner">
	<p class="promo-banner__text">
		<?php
		printf(
			/* translators: %s: nombre del sitio. */
			esc_html__( 'Recibe %s en tu WhatsApp', 'dnorte-theme' ),
			esc_html( get_bloginfo( 'name' ) )
		);
		?>
	</p>
	<a class="promo-banner__cta" href="<?php echo esc_url( 'https://wa.me/' . $digitsOnly ); ?>" target="_blank" rel="noopener noreferrer">
		<?php esc_html_e( 'Escríbenos', 'dnorte-theme' ); ?>
	</a>
</div>
