<?php
/**
 * Emite un `<script>` mínimo en `wp_footer` que registra la visita vía
 * `navigator.sendBeacon()` — no toca `WP_Post`/`WP_Query` directamente (solo
 * `is_singular()`/`get_queried_object_id()`, funciones simples), así que se puede
 * probar con Brain Monkey a nivel unitario, a diferencia de PageviewRepository.
 *
 * Deliberadamente excluye a los usuarios con capacidad de editar contenido
 * (`edit_posts`): sin esto, cada vista previa/revisión de un artículo por el propio
 * equipo editorial contaminaría las estadísticas de lectura real.
 *
 * @package DNorteCore\Analytics
 */

declare(strict_types=1);

namespace DNorteCore\Analytics;

use DNorteCore\Config\Config;

final class PageviewBeaconRenderer {

	public function __construct( private readonly Config $config ) {
	}

	public function render(): void {
		if ( ! $this->shouldRecord() ) {
			return;
		}

		$postId = get_queried_object_id();

		if ( $postId <= 0 ) {
			return;
		}

		$endpoint = rest_url( 'dnorte/v1/analytics/pageview' );

		printf(
			'<script>(function(){if(!navigator.sendBeacon){return;}var p=new Blob([JSON.stringify({post_id:%d,referrer:document.referrer})],{type:"application/json"});navigator.sendBeacon(%s,p);})();</script>',
			$postId, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sale de get_queried_object_id() (siempre int) y %d lo fuerza igual; contexto JS, no HTML. Mismo criterio que el XML/JSON de NewsSitemapController.
			wp_json_encode( esc_url_raw( $endpoint ) )
		);
	}

	private function shouldRecord(): bool {
		if ( current_user_can( 'edit_posts' ) ) {
			return false;
		}

		/** @var list<string> $postTypes */
		$postTypes = $this->config->get( 'analytics.tracked_post_types', array( 'post' ) );

		return is_singular( $postTypes );
	}
}
