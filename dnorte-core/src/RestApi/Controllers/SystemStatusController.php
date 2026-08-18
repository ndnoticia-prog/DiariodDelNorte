<?php
/**
 * Endpoint de estado público (sin datos sensibles): confirma que dnorte-core está
 * activo y responde a wp-json, y qué versión de plugin/tema/WordPress hay corriendo.
 * Mismo propósito que `GET /wp-json/nd/v1/system/status` en ND Platform.
 *
 * @package DNorteCore\RestApi\Controllers
 */

declare(strict_types=1);

namespace DNorteCore\RestApi\Controllers;

use DNorteCore\RestApi\Contracts\RegistersRoutes;
use DNorteCore\Routing\Router;
use WP_REST_Request;
use WP_REST_Response;

final class SystemStatusController implements RegistersRoutes {

	public function registerRoutes( Router $router ): void {
		$router->register(
			'dnorte/v1',
			'/system/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function handle( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'plugin'         => 'dnorte-core',
				'plugin_version' => defined( 'DNORTE_CORE_VERSION' ) ? DNORTE_CORE_VERSION : null,
				'theme_active'   => wp_get_theme()->get( 'Name' ),
				'theme_version'  => wp_get_theme()->get( 'Version' ),
				'wordpress'      => get_bloginfo( 'version' ),
				'php'            => PHP_VERSION,
			)
		);
	}
}
