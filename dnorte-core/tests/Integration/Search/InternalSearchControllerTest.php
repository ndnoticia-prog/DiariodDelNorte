<?php
/**
 * Cierra el hueco documentado en la suite unitaria (InternalSearchControllerTest ahí
 * solo cubre registerRoutes(), no handle(), porque WP_Query/WP_REST_Response no
 * existen fuera de un WordPress real). Aquí sí se ejecuta el endpoint real de punta
 * a punta, incluido el ranking por relevancia real que aporta SearchQueryModifier
 * (el propio Application ya arrancó el módulo de búsqueda durante el bootstrap de
 * pruebas, exactamente igual que en un sitio real).
 *
 * Artículos de fixture creados en wpSetUpBeforeClass(), no dentro de cada método —
 * mismo motivo documentado en SearchQueryModifierTest (InnoDB no hace visibles
 * filas insertadas en la misma transacción sin confirmar a un MATCH ... AGAINST).
 *
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Search;

use WP_REST_Request;
use WP_UnitTestCase;

final class InternalSearchControllerTest extends WP_UnitTestCase {

	private static int $matchingPostId;

	public static function wpSetUpBeforeClass( \WP_UnitTest_Factory $factory ): void {
		self::$matchingPostId = $factory->post->create(
			array(
				'post_title'   => 'Concejo municipal aprueba el presupuesto',
				'post_content' => 'El concejo municipal debatió el presupuesto durante horas.',
			)
		);
		$factory->post->create( array( 'post_title' => 'Un artículo sin relación' ) );
		$factory->post->create(
			array(
				'post_status'  => 'draft',
				'post_title'   => 'Concejo municipal en borrador',
				'post_content' => 'Artículo todavía sin publicar sobre el concejo municipal.',
			)
		);
	}

	public function test_search_endpoint_returns_only_the_matching_published_article(): void {
		do_action( 'rest_api_init' );

		$request = new WP_REST_Request( 'GET', '/dnorte/v1/search' );
		$request->set_param( 'q', 'concejo municipal' );

		$response = rest_get_server()->dispatch( $request );

		self::assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		self::assertCount( 1, $data['results'] );
		self::assertSame( self::$matchingPostId, $data['results'][0]['id'] );
		self::assertSame( 'Concejo municipal aprueba el presupuesto', $data['results'][0]['title'] );
		self::assertArrayHasKey( 'url', $data['results'][0] );
		self::assertArrayHasKey( 'excerpt', $data['results'][0] );
	}

	public function test_search_endpoint_returns_no_results_for_a_term_below_the_minimum_length(): void {
		do_action( 'rest_api_init' );

		$request = new WP_REST_Request( 'GET', '/dnorte/v1/search' );
		$request->set_param( 'q', 'el' );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		self::assertSame( array(), $data['results'] );
	}
}
