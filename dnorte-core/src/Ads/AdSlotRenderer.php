<?php
/**
 * Envuelve el HTML de un anuncio en el contenedor que espera app.scss
 * (`.dnorte-ad`/`.dnorte-ad--{slot}`), o devuelve una cadena vacía si no hay
 * anuncio activo — nunca un contenedor vacío que dejaría un hueco visual.
 *
 * Solo depende de funciones de WordPress (esc_attr()), no de WP_Post/WP_Query, así
 * que se puede probar con Brain Monkey a nivel unitario.
 *
 * @package DNorteCore\Ads
 */

declare(strict_types=1);

namespace DNorteCore\Ads;

use DateTimeImmutable;

final class AdSlotRenderer {

	public function render( ?Ad $ad, string $slotKey, DateTimeImmutable $now ): string {
		if ( $ad === null || ! $ad->isActiveAt( $now ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $ad->html es marcado (banner/etiqueta de red publicitaria) guardado por un administrador (capacidad manage_options, ver AdsAdminPage) a propósito, no contenido de un visitante: el mismo nivel de confianza que WordPress ya da a the_content() de un usuario con unfiltered_html. slotKey sale siempre de una clave fija de config/ads.php, nunca de un valor externo.
		return sprintf(
			'<div class="dnorte-ad dnorte-ad--%s">%s</div>',
			esc_attr( $slotKey ),
			$ad->html
		);
	}
}
