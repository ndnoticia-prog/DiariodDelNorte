<?php
/**
 * `POST /wp-json/dnorte/v1/analytics/pageview` — recibe el beacon que emite
 * PageviewBeaconRenderer en cada artículo.
 *
 * Sin dependencias en el constructor a propósito, a diferencia del resto de
 * controladores con lógica que sí toca la base de datos: PageviewRepository
 * depende en cadena de DatabaseManager → wpdb, y wpdb no existe en el proceso de
 * pruebas unitarias (Brain Monkey) — inyectarlo aquí rompería la prueba unitaria
 * de registerRoutes() (que solo necesita instanciar el controlador, no ejecutar
 * handle()). handle() arma el repositorio a mano con `global $wpdb`, mismo patrón
 * ya usado en el activation hook de dnorte-core.php por el mismo motivo. Mismo
 * límite documentado para DatabaseManager/Migrator/Installer en
 * docs/Architecture.md.
 *
 * @package DNorteCore\Analytics
 */

declare(strict_types=1);

namespace DNorteCore\Analytics;

use DNorteCore\Analytics\Pageviews\PageviewRepository;
use DNorteCore\Database\DatabaseManager;
use DNorteCore\RestApi\Contracts\RegistersRoutes;
use DNorteCore\Routing\Router;
use WP_REST_Request;
use WP_REST_Response;

final class PageviewController implements RegistersRoutes {

	public function registerRoutes( Router $router ): void {
		$router->register(
			'dnorte/v1',
			'/analytics/pageview',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'post_id'  => array(
						'type'     => 'integer',
						'required' => true,
					),
					'referrer' => array(
						'type'     => 'string',
						'required' => false,
					),
				),
			)
		);
	}

	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$postId = (int) $request->get_param( 'post_id' );

		if ( get_post_status( $postId ) !== 'publish' ) {
			return new WP_REST_Response( null, 204 );
		}

		global $wpdb;
		$repository = new PageviewRepository( new DatabaseManager( $wpdb ) );

		$referrer = (string) $request->get_param( 'referrer' );
		$repository->record( $postId, $this->extractHost( $referrer ) );

		return new WP_REST_Response( null, 204 );
	}

	private function extractHost( string $referrer ): ?string {
		if ( $referrer === '' ) {
			return null;
		}

		$host = wp_parse_url( $referrer, PHP_URL_HOST );

		return is_string( $host ) && $host !== '' ? $host : null;
	}
}
