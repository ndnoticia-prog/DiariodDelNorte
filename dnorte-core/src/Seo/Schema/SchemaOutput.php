<?php
/**
 * Un único <script type="application/ld+json"> con un @graph (no un <script> por
 * tipo). JSON_HEX_TAG | JSON_HEX_AMP: sin esos flags, un título de artículo que
 * contuviera literalmente `</script>` cerraría el bloque e inyectaría HTML/JS
 * arbitrario en la página — vulnerabilidad real y conocida en plugins de SEO para
 * WordPress. Mismo criterio que ND Platform (ver docs/handoff-nd-platform.md §3).
 *
 * @package DNorteCore\Seo\Schema
 */

declare(strict_types=1);

namespace DNorteCore\Seo\Schema;

final class SchemaOutput {

	/**
	 * @param list<array<string, mixed>> $nodes
	 */
	public function toScript( array $nodes ): string {
		if ( $nodes === array() ) {
			return '';
		}

		$graph = array(
			'@context' => 'https://schema.org',
			'@graph'   => array_values( $nodes ),
		);

		$json = wp_json_encode( $graph, JSON_HEX_TAG | JSON_HEX_AMP );

		if ( $json === false ) {
			return '';
		}

		return sprintf( '<script type="application/ld+json">%s</script>', $json );
	}
}
