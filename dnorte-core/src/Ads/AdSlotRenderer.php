<?php
/**
 * Envuelve el marcado de una campaña en el contenedor que espera app.scss
 * (`.dnorte-ad`/`.dnorte-ad--{slot}`), con `data-campaign-id` para que el script
 * de seguimiento de AdsServiceProvider::renderTrackingScript() sepa a qué campaña
 * atribuir la impresión/clic — o devuelve una cadena vacía sin campaña, nunca un
 * contenedor vacío que dejaría un hueco visual. Ya no decide qué campaña
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

		$inner = match ( $campaign->type ) {
			Campaign::TYPE_ADSENSE => $this->renderAdSenseUnit( $campaign ),
			Campaign::TYPE_IMAGE   => $this->renderImageBanner( $campaign ),
			default                => $campaign->html,
		};

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $inner es marcado (banner/etiqueta de red publicitaria) guardado por un administrador (capacidad manage_options, ver AdsAdminPage) a propósito, no contenido de un visitante: el mismo nivel de confianza que WordPress ya da a the_content() de un usuario con unfiltered_html. slotKey/campaign->id salen siempre de datos propios (config/ads.php y la fila de la campaña), nunca de un valor externo.
		return sprintf(
			'<div class="dnorte-ad dnorte-ad--%s" data-campaign-id="%d">%s</div>',
			esc_attr( $slotKey ),
			$campaign->id,
			$inner
		);
	}

	/**
	 * Solo el `<ins>` de la unidad — el `<script>` que carga adsbygoogle.js una
	 * única vez por página vive en AdsServiceProvider::enqueueAdSenseLoader()
	 * (wp_enqueue_scripts), no aquí: repetirlo en cada espacio sería redundante si
	 * dos campañas de AdSense aparecen en la misma página. Los clics dentro de la
	 * unidad de AdSense los cuenta y factura Google, no dnorte-core — el
	 * "data-campaign-id" del contenedor solo sirve para nuestras propias
	 * impresiones/clics en los otros dos tipos.
	 */
	private function renderAdSenseUnit( Campaign $campaign ): string {
		return sprintf(
			'<ins class="adsbygoogle" style="display:block" data-ad-client="%s" data-ad-slot="%s" data-ad-format="auto" data-full-width-responsive="true"></ins><script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>',
			esc_attr( $campaign->adsenseClientId ),
			esc_attr( $campaign->adsenseSlotId )
		);
	}

	private function renderImageBanner( Campaign $campaign ): string {
		return sprintf(
			'<a href="%s" target="_blank" rel="noopener sponsored"><img src="%s" alt="%s" loading="lazy" /></a>',
			esc_url( $campaign->linkUrl ),
			esc_url( $campaign->imageUrl ),
			esc_attr( $campaign->name )
		);
	}
}
