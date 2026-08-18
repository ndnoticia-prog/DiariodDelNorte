<?php
/**
 * Resuelve el SeoContext de la página actual: singular, home, archivo, búsqueda, 404.
 * Único lugar de la plataforma que traduce el estado de la consulta principal de
 * WordPress (is_singular(), is_search(), ...) a datos SEO — meta tags, OpenGraph,
 * Twitter Cards y Schema.org consumen siempre el resultado, nunca resuelven el
 * contexto por su cuenta.
 *
 * @package DNorteCore\Seo\Context
 */

declare(strict_types=1);

namespace DNorteCore\Seo\Context;

use WP_Post;

final class SeoContextResolver {

	public function resolve(): SeoContext {
		if ( is_singular() ) {
			return $this->forSingular();
		}

		if ( is_search() ) {
			return $this->forSearch();
		}

		if ( is_404() ) {
			return $this->for404();
		}

		if ( is_archive() ) {
			return $this->forArchive();
		}

		return $this->forHome();
	}

	// Tamaño registrado por Media\FeaturedImageSize (1200x675, requisito de Discover)
	// — referenciado como cadena literal, no como dependencia de clase, para no
	// acoplar este resolver al módulo de multimedia. Si el tamaño no existe (o el
	// post no tiene imagen destacada de ese tamaño), get_the_post_thumbnail_url()
	// devuelve false y se cae a 'large' explícitamente, sin fatal error. Mismo
	// patrón que ND Platform entre nd-seo y nd-discover.
	private const FEATURED_IMAGE_SIZE = 'dnorte-featured';

	private function forSingular(): SeoContext {
		$queried = get_queried_object();
		$post    = $queried instanceof WP_Post ? $queried : null;

		$image = $post !== null ? $this->featuredImageUrl( $post ) : false;

		return new SeoContext(
			title: $post !== null ? get_the_title( $post ) : wp_get_document_title(),
			description: $post !== null ? $this->excerptFor( $post ) : '',
			canonicalUrl: $post !== null ? (string) get_permalink( $post ) : $this->currentUrl(),
			noindex: false,
			ogType: $post !== null && $post->post_type === 'post' ? 'article' : 'website',
			imageUrl: $image !== false ? $image : null,
			post: $post
		);
	}

	private function forHome(): SeoContext {
		return new SeoContext(
			title: get_bloginfo( 'name' ),
			description: get_bloginfo( 'description' ),
			canonicalUrl: home_url( '/' ),
			noindex: false,
			ogType: 'website',
			imageUrl: null
		);
	}

	private function forArchive(): SeoContext {
		return new SeoContext(
			title: wp_strip_all_tags( get_the_archive_title() ),
			description: wp_strip_all_tags( (string) get_the_archive_description() ),
			canonicalUrl: $this->currentUrl(),
			noindex: false,
			ogType: 'website',
			imageUrl: null
		);
	}

	private function forSearch(): SeoContext {
		return new SeoContext(
			/* translators: %s: término buscado. */
			title: sprintf( __( 'Resultados de búsqueda: %s', 'dnorte-core' ), get_search_query() ),
			description: '',
			canonicalUrl: $this->currentUrl(),
			noindex: true,
			ogType: 'website',
			imageUrl: null
		);
	}

	private function for404(): SeoContext {
		return new SeoContext(
			title: __( 'Página no encontrada', 'dnorte-core' ),
			description: '',
			canonicalUrl: home_url( '/' ),
			noindex: true,
			ogType: 'website',
			imageUrl: null
		);
	}

	private function featuredImageUrl( WP_Post $post ): string|false {
		$image = get_the_post_thumbnail_url( $post, self::FEATURED_IMAGE_SIZE );

		return $image !== false ? $image : get_the_post_thumbnail_url( $post, 'large' );
	}

	private function excerptFor( WP_Post $post ): string {
		$excerpt = get_the_excerpt( $post );

		return is_string( $excerpt ) ? wp_strip_all_tags( $excerpt ) : '';
	}

	private function currentUrl(): string {
		global $wp;

		return home_url( add_query_arg( array(), $wp->request ?? '' ) );
	}
}
