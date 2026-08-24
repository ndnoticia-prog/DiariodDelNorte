<?php
/**
 * Envuelve el marcado de una campaña en el contenedor que espera app.scss
 * (`.dnorte-ad`/`.dnorte-ad--{slot}`), o devuelve una cadena vacía sin campaña —
 * nunca un contenedor vacío que dejaría un hueco visual. Ya no decide qué campaña
 * corresponde ni si está activa (eso lo resuelve CampaignRepository::forZone()
 * antes de llegar aquí) — solo sabe dibujar la que le pasan.
 *
 * Solo depende de funciones de WordPress (esc_attr()), no de WP_Post/WP_Query, así
 * que se puede probar con Brain Monkey a nivel unitario.
 *
 * @package DNorteCore\Ads
 */

declare(strict_types=1);

namespace DNorteCore\Ads;

final class AdSlotRenderer {

	public function render( ?Campaign $campaign, string $slotKey ): string {
		if ( $campaign === null ) {
			return '';
		}

		$inner = $campaign->type === Campaign::TYPE_ADSENSE
			? $this->renderAdSenseUnit( $campaign )
			: $campaign->html;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $inner es marcado (banner/etiqueta de red publicitaria) guardado por un administrador (capacidad manage_options, ver AdsAdminPage) a propósito, no contenido de un visitante: el mismo nivel de confianza que WordPress ya da a the_content() de un usuario con unfiltered_html. slotKey sale siempre de una clave fija de config/ads.php, nunca de un valor externo.
		return sprintf(
			'<div class="dnorte-ad dnorte-ad--%s">%s</div>',
			esc_attr( $slotKey ),
			$inner
		);
	}

	/**
	 * Solo el `<ins>` de la unidad — el `<script>` que carga adsbygoogle.js una
	 * única vez por página vive en AdsServiceProvider::renderAdSenseLoader()
	 * (wp_head), no aquí: repetirlo en cada espacio sería redundante si dos
	 * campañas de AdSense aparecen en la misma página.
	 */
	private function renderAdSenseUnit( Campaign $campaign ): string {
		return sprintf(
			'<ins class="adsbygoogle" style="display:block" data-ad-client="%s" data-ad-slot="%s" data-ad-format="auto" data-full-width-responsive="true"></ins><script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>',
			esc_attr( $campaign->adsenseClientId ),
			esc_attr( $campaign->adsenseSlotId )
		);
	}
}
