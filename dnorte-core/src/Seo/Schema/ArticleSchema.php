<?php
/**
 * NewsArticle en vez de Article genérico: mismo tipo que espera Google News/Discover
 * para contenido editorial. Depende de WP_Post real — ver
 * "Por qué DatabaseManager/Migrator/Installer no tienen pruebas unitarias con mocks"
 * en docs/Architecture.md (misma limitación aplica aquí: se cubre con pruebas de
 * integración, no unitarias).
 *
 * @package DNorteCore\Seo\Schema
 */

declare(strict_types=1);

namespace DNorteCore\Seo\Schema;

use WP_Post;

final class ArticleSchema {

	public function __construct( private readonly string $siteUrl ) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function build( WP_Post $post ): array {
		return array(
			'@type'            => 'NewsArticle',
			'@id'              => get_permalink( $post ) . '#article',
			'headline'         => get_the_title( $post ),
			'datePublished'    => get_the_date( DATE_W3C, $post ),
			'dateModified'     => get_the_modified_date( DATE_W3C, $post ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', (int) $post->post_author ),
			),
			'publisher'        => array( '@id' => $this->siteUrl . '#organization' ),
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => (string) get_permalink( $post ),
			),
		);
	}
}
