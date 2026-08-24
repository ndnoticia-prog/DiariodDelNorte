<?php
/**
 * `POST /wp-json/dnorte/v1/ads/impression` y `.../click` — reciben el beacon que
 * emite el script de seguimiento inyectado por AdsServiceProvider::renderTrackingScript()
 * (`wp_footer`) para alimentar la columna "Estadísticas" del panel de Publicidad.
 *
 * Sin dependencias en el constructor a propósito, igual que PageviewController:
 * CampaignRepository depende en cadena de DatabaseManager → wpdb, inexistente en
 * el proceso de pruebas unitarias — inyectarlo aquí rompería la prueba unitaria de
 * registerRoutes(). handle() arma el repositorio a mano con `global $wpdb`.
 *
 * Limitación conocida y deliberada (mismo criterio que Analytics\PageviewController):
 * sin deduplicación ni detección de bots — un recuento aproximado para uso propio,
 * no una fuente verificada de facturación con el anunciante.
 *
 * @package DNorteCore\Ads
 */

declare(strict_types=1);

namespace DNorteCore\Ads;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\RestApi\Contracts\RegistersRoutes;
use DNorteCore\Routing\Router;
use WP_REST_Request;
use WP_REST_Response;

final class CampaignEventController implements RegistersRoutes {

	public function registerRoutes( Router $router ): void {
		$args = array(
			'campaign_id' => array(
				'type'     => 'integer',
				'required' => true,
			),
		);

		$router->register(
			'dnorte/v1',
			'/ads/impression',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handleImpression' ),
				'permission_callback' => '__return_true',
				'args'                => $args,
			)
		);

		$router->register(
			'dnorte/v1',
			'/ads/click',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handleClick' ),
				'permission_callback' => '__return_true',
				'args'                => $args,
			)
		);
	}

	public function handleImpression( WP_REST_Request $request ): WP_REST_Response {
		$this->repository()->recordImpression( (int) $request->get_param( 'campaign_id' ) );

		return new WP_REST_Response( null, 204 );
	}

	public function handleClick( WP_REST_Request $request ): WP_REST_Response {
		$this->repository()->recordClick( (int) $request->get_param( 'campaign_id' ) );

		return new WP_REST_Response( null, 204 );
	}

	private function repository(): CampaignRepository {
		global $wpdb;

		return new CampaignRepository( new DatabaseManager( $wpdb ) );
	}
}
