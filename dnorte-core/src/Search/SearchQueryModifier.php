<?php
/**
 * Sustituye el `LIKE '%término%'` que WordPress usa por defecto en cualquier
 * WP_Query de búsqueda por un MATCH ... AGAINST sobre el índice FULLTEXT que crea
 * CreateSearchFulltextIndex, y ordena por relevancia real en vez de por fecha. Se
 * engancha vía los filtros nativos `posts_search`/`posts_orderby` — WordPress ya
 * resuelve todo lo demás (paginación, post_status, permisos) sin reimplementar
 * nada de eso.
 *
 * Se aplica a CUALQUIER WP_Query cuyo is_search() sea true, no solo a la consulta
 * principal de una página de resultados — así el mismo filtro cubre tanto
 * search.php como el WP_Query que arma InternalSearchController para la caja de
 * búsqueda en vivo, sin duplicar la lógica de relevancia en dos sitios.
 *
 * @package DNorteCore\Search
 */

declare(strict_types=1);

namespace DNorteCore\Search;

use DNorteCore\Config\Config;
use DNorteCore\Database\DatabaseManager;
use WP_Query;

final class SearchQueryModifier {

	public function __construct(
		private readonly DatabaseManager $database,
		private readonly Config $config,
		private readonly BooleanModeTermBuilder $termBuilder
	) {
	}

	public function modifySearch( string $search, WP_Query $query ): string {
		$fragment = $this->matchAgainstFragment( $query );

		return $fragment === null ? $search : " AND {$fragment} ";
	}

	public function modifyOrderby( string $orderby, WP_Query $query ): string {
		$fragment = $this->matchAgainstFragment( $query );

		return $fragment === null ? $orderby : "{$fragment} DESC";
	}

	/**
	 * null significa "deja el comportamiento nativo de WordPress" — o bien la
	 * consulta no es una búsqueda, o el término (tras sanitizar) quedó demasiado
	 * corto/vacío para tener sentido con MATCH AGAINST.
	 */
	private function matchAgainstFragment( WP_Query $query ): ?string {
		if ( ! $query->is_search() ) {
			return null;
		}

		$term = trim( (string) $query->get( 's' ) );

		/** @var int $minLength */
		$minLength = $this->config->get( 'search.min_query_length', 3 );

		if ( mb_strlen( $term ) < $minLength ) {
			return null;
		}

		$boolean = $this->termBuilder->build( $term );

		if ( $boolean === '' ) {
			return null;
		}

		$postsTable = $this->database->wpTable( 'posts' );

		return $this->database->fragment(
			"MATCH({$postsTable}.post_title, {$postsTable}.post_content) AGAINST (%s IN BOOLEAN MODE)",
			array( $boolean )
		);
	}
}
