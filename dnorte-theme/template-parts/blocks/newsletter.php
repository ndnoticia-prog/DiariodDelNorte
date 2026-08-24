<?php
/**
 * Suscripción al newsletter — envía a POST /wp-json/dnorte/v1/newsletter/
 * subscribe (dnorte-core, Newsletter\NewsletterController) vía JS
 * (initNewsletterForm(), assets/js/app.js): un correo real, guardado de
 * verdad (Newsletter\Subscribers\NewsletterSubscriberRepository, panel
 * "Newsletter" en wp-admin), no un formulario decorativo que no lleva a
 * ningún sitio. Sin JS, el formulario hace un POST normal a la misma URL vía
 * <form method="post"> — degrada mostrando la respuesta JSON cruda en vez de
 * la confirmación en pantalla, aceptable para el caso raro de JS
 * desactivado, ver el mismo criterio en searchform.php.
 *
 * @package DNorteTheme
 */

declare(strict_types=1);

?>
<section class="newsletter" id="suscribete">
	<div class="newsletter__inner">
		<h2 class="newsletter__title"><?php esc_html_e( 'Recibe lo más importante de La Guajira', 'dnorte-theme' ); ?></h2>
		<p class="newsletter__subtitle"><?php esc_html_e( 'Las noticias directamente en tu correo.', 'dnorte-theme' ); ?></p>

		<form class="newsletter__form" method="post" action="<?php echo esc_url( rest_url( 'dnorte/v1/newsletter/subscribe' ) ); ?>" data-newsletter-form>
			<label class="screen-reader-text" for="dnorte-newsletter-email"><?php esc_html_e( 'Tu correo electrónico', 'dnorte-theme' ); ?></label>
			<input
				type="email"
				id="dnorte-newsletter-email"
				name="email"
				class="newsletter__field"
				placeholder="<?php esc_attr_e( 'Tu correo electrónico', 'dnorte-theme' ); ?>"
				required
			/>
			<button type="submit" class="newsletter__submit"><?php esc_html_e( 'Suscribirme', 'dnorte-theme' ); ?></button>
		</form>
		<p class="newsletter__status" data-newsletter-status aria-live="polite"></p>
	</div>
</section>
