<?php
/**
 * Traduce el término de búsqueda tal cual lo escribe el visitante a la sintaxis de
 * MySQL "IN BOOLEAN MODE" — pieza pura (ninguna dependencia de WordPress), separada
 * de SearchQueryModifier para poder probarla con PHPUnit/Brain Monkey sin necesitar
 * un WP_Query real (mismo criterio que NewsSitemapController::render() vs.
 * recentArticleData()).
 *
 * @package DNorteCore\Search
 */

declare(strict_types=1);

namespace DNorteCore\Search;

final class BooleanModeTermBuilder {

	/**
	 * Cada palabra se limpia de los operadores reservados de modo booleano
	 * (+ - < > ( ) ~ * " @) y se le añade un `*` final para que la búsqueda
	 * funcione como "empieza por" — útil para una caja de búsqueda con
	 * sugerencias en vivo, donde el visitante todavía no terminó de escribir la
	 * palabra completa. Palabras que quedan vacías tras la limpieza (ej. un
	 * término formado solo por esos símbolos) se descartan.
	 */
	public function build( string $term ): string {
		$words = preg_split( '/\s+/', trim( $term ) );
		$words = $words === false ? array() : $words;

		$prefixed = array_filter(
			array_map( array( $this, 'sanitizeWord' ), $words ),
			static fn ( string $word ): bool => $word !== ''
		);

		return implode( ' ', $prefixed );
	}

	private function sanitizeWord( string $word ): string {
		$clean = preg_replace( '/[+\-<>()~*"@]+/', '', $word );
		$clean = $clean === null ? '' : $clean;

		return $clean === '' ? '' : $clean . '*';
	}
}
