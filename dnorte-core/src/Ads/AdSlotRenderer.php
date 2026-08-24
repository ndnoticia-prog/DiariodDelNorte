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
 * Solo depende de funciones de WordPress (esc_attr()/esc_url()/wp_json_encode()),
 * no de WP_Post/WP_Query, así que se puede probar con Brain Monkey a nivel unitario.
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
			Campaign::TYPE_ADSENSE   => $this->renderAdSenseUnit( $campaign ),
			Campaign::TYPE_GAM       => $this->renderGamSlot( $campaign ),
			Campaign::TYPE_IMAGE     => $this->renderImageBanner( $campaign ),
			Campaign::TYPE_VIDEO     => $this->renderVideoBanner( $campaign ),
			Campaign::TYPE_SPONSORED => $this->renderSponsored( $campaign ),
			default                  => $campaign->html,
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
	 * impresiones/clics en los demás tipos.
	 */
	private function renderAdSenseUnit( Campaign $campaign ): string {
		return sprintf(
			'<ins class="adsbygoogle" style="display:block" data-ad-client="%s" data-ad-slot="%s" data-ad-format="auto" data-full-width-responsive="true"></ins><script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>',
			esc_attr( $campaign->adsenseClientId ),
			esc_attr( $campaign->adsenseSlotId )
		);
	}

	/**
	 * `gpt.js` (la librería de Google Ad Manager) se encola una única vez por
	 * página en AdsServiceProvider::enqueueGamLoader() (wp_enqueue_scripts), mismo
	 * criterio que AdSense — aquí solo se define y muestra la unidad concreta.
	 * Mismos clics/impresiones sin visibilidad para dnorte-core que en AdSense: los
	 * mide y los factura Google.
	 */
	private function renderGamSlot( Campaign $campaign ): string {
		$sizes = $this->parseGamSizes( $campaign->gamSizes );

		if ( $sizes === array() ) {
			return '';
		}

		$divId = 'dnorte-gam-' . $campaign->id;

		return sprintf(
			'<div id="%1$s"></div><script>window.googletag=window.googletag||{cmd:[]};googletag.cmd.push(function(){googletag.defineSlot(%2$s,%3$s,%4$s).addService(googletag.pubads());googletag.pubads().enableSingleRequest();googletag.enableServices();googletag.display(%4$s);});</script>',
			esc_attr( $divId ),
			wp_json_encode( $campaign->gamAdUnitPath ),
			wp_json_encode( $sizes ),
			wp_json_encode( $divId )
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

	/**
	 * Banner de vídeo propio (autoreproducido, silenciado, en bucle) — no un
	 * anuncio de pre-roll con VAST/VMAP servido por un ad server de vídeo, fuera
	 * de alcance por ahora.
	 */
	private function renderVideoBanner( Campaign $campaign ): string {
		return sprintf(
			'<a href="%s" target="_blank" rel="noopener sponsored"><video src="%s" autoplay muted loop playsinline aria-label="%s"></video></a>',
			esc_url( $campaign->linkUrl ),
			esc_url( $campaign->videoUrl ),
			esc_attr( $campaign->name )
		);
	}

	/**
	 * Igual que el banner de imagen, con un texto descriptivo corto debajo (ej.
	 * "Descubre la nueva colección de...") — pensado para contenido patrocinado
	 * tipo nativo, no un simple banner publicitario.
	 */
	private function renderSponsored( Campaign $campaign ): string {
		return sprintf(
			'<a href="%s" target="_blank" rel="noopener sponsored"><img src="%s" alt="%s" loading="lazy" /><span class="dnorte-ad__description">%s</span></a>',
			esc_url( $campaign->linkUrl ),
			esc_url( $campaign->imageUrl ),
			esc_attr( $campaign->name ),
			esc_html( $campaign->description )
		);
	}

	/**
	 * "728x90,970x250" → [[728, 90], [970, 250]]. Un par mal escrito se descarta en
	 * vez de romper la página — mismo criterio que Search\BooleanModeTermBuilder
	 * ante entrada imperfecta.
	 *
	 * @return list<array{0: int, 1: int}>
	 */
	private function parseGamSizes( string $raw ): array {
		$sizes = array();

		foreach ( explode( ',', $raw ) as $pair ) {
			$pair = trim( $pair );

			if ( preg_match( '/^(\d+)x(\d+)$/i', $pair, $matches ) === 1 ) {
				$sizes[] = array( (int) $matches[1], (int) $matches[2] );
			}
		}

		return $sizes;
	}
}
