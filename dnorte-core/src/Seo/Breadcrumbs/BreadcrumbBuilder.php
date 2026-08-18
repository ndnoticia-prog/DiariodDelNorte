<?php
/**
 * Resuelve la lista de breadcrumbs del contexto actual. Depende de funciones que a su
 * vez consultan WP_Post/taxonomías reales (get_the_category(), get_queried_object())
 * — cubierto por pruebas de integración, no unitarias (ver docs/Architecture.md).
 *
 * @package DNorteCore\Seo\Breadcrumbs
 */

declare(strict_types=1);

namespace DNorteCore\Seo\Breadcrumbs;

use WP_Post;
use WP_Term;

final class BreadcrumbBuilder {

	public function __construct(
		private readonly string $siteName,
		private readonly string $siteUrl
	) {
	}

	/**
	 * @return list<array{name: string, url: string}>
	 */
	public function build(): array {
		$items = array(
			array(
				'name' => $this->siteName,
				'url'  => $this->siteUrl,
			),
		);

		if ( is_singular() ) {
			$queried = get_queried_object();
			$post    = $queried instanceof WP_Post ? $queried : null;

			if ( $post !== null ) {
				$categories = get_the_category( $post->ID );

				if ( $categories !== array() ) {
					$items[] = array(
						'name' => $categories[0]->name,
						'url'  => (string) get_category_link( $categories[0] ),
					);
				}

				$items[] = array(
					'name' => get_the_title( $post ),
					'url'  => (string) get_permalink( $post ),
				);
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			// El nombre del término directo (no get_the_archive_title()): WordPress
			// core antepone "Category: "/"Tag: " al título de archivo — correcto
			// para el <title> SEO, pero no para una miga de pan. Encontrado por la
			// prueba de integración, no por revisión manual.
			$term    = get_queried_object();
			$items[] = array(
				'name' => $term instanceof WP_Term ? $term->name : wp_strip_all_tags( get_the_archive_title() ),
				'url'  => $this->currentUrl(),
			);
		} elseif ( is_search() ) {
			$items[] = array(
				/* translators: %s: término buscado. */
				'name' => sprintf( __( 'Resultados para: %s', 'dnorte-core' ), get_search_query() ),
				'url'  => $this->currentUrl(),
			);
		}

		return $items;
	}

	private function currentUrl(): string {
		global $wp;

		return home_url( add_query_arg( array(), $wp->request ?? '' ) );
	}
}
