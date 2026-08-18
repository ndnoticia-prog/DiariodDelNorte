<?php
/**
 * Cierra el hueco documentado en la suite unitaria (SystemStatusControllerTest ahí
 * solo cubre registerRoutes(), no handle(), porque WP_REST_Response no existe fuera
 * de un WordPress real). Aquí sí se ejecuta el endpoint real de punta a punta.
 *
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\RestApi\Controllers;

use WP_REST_Request;
use WP_UnitTestCase;

final class SystemStatusControllerTest extends WP_UnitTestCase {

	public function test_status_endpoint_responds_with_the_expected_payload(): void {
		do_action( 'rest_api_init' );

		$request  = new WP_REST_Request( 'GET', '/dnorte/v1/system/status' );
		$response = rest_get_server()->dispatch( $request );

		self::assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		self::assertSame( 'dnorte-core', $data['plugin'] );
		self::assertSame( DNORTE_CORE_VERSION, $data['plugin_version'] );
		self::assertArrayHasKey( 'wordpress', $data ); // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- clave literal del JSON del endpoint, no prosa.
		self::assertArrayHasKey( 'php', $data );
	}

	public function test_status_endpoint_is_public_and_requires_no_authentication(): void {
		do_action( 'rest_api_init' );

		wp_set_current_user( 0 ); // Visitante anónimo, explícito.

		$request  = new WP_REST_Request( 'GET', '/dnorte/v1/system/status' );
		$response = rest_get_server()->dispatch( $request );

		self::assertSame( 200, $response->get_status() );
	}
}
