<?php
/**
 * `GET /wp-json/dnorte/v1/search?q=...` — endpoint ligero pensado para una caja de
 * búsqueda con sugerencias en vivo (título, extracto, URL, fecha; sin el contenido
 * completo del artículo). Reutiliza WP_Query con `s`, así que se beneficia
 * automáticamente del ranking por relevancia de SearchQueryModifier sin duplicar
 * esa lógica — el mismo criterio documentado ahí.
 *
 * @package DNorteCore\Search
 */

declare(strict_types=1);

namespace DNorteCore\Search;

use DNorteCore\Config\Config;
use DNorteCore\RestApi\Contracts\RegistersRoutes;
use DNorteCore\Routing\Router;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

final class InternalSearchController implements RegistersRoutes {

	public function __construct( private readonly Config $config ) {
	}

	public function registerRoutes( Router $router ): void {
		$router->register(
			'dnorte/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
	}

	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$term = trim( (string) $request->get_param( 'q' ) );

		/** @var int $minLength */
		$minLength = $this->config->get( 'search.min_query_length', 3 );

		if ( mb_strlen( $term ) < $minLength ) {
			return new WP_REST_Response( array( 'results' => array() ) );
		}

		/** @var list<string> $postTypes */
		$postTypes = $this->config->get( 'search.post_types', array( 'post' ) );

		/** @var int $limit */
		$limit = $this->config->get( 'search.results_per_request', 8 );

		$query = new WP_Query(
			array(
				's'                   => $term,
				'post_type'           => $postTypes,
				'post_status'         => 'publish',
				'posts_per_page'      => $limit,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);

		/** @var list<WP_Post> $posts */
		$posts   = $query->posts;
		$results = array_map( array( $this, 'formatResult' ), $posts );

		return new WP_REST_Response( array( 'results' => array_values( $results ) ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function formatResult( WP_Post $post ): array {
		return array(
			'id'      => $post->ID,
			'title'   => get_the_title( $post ),
			'excerpt' => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'url'     => get_permalink( $post ),
			'date'    => get_the_date( 'c', $post ),
		);
	}
}
