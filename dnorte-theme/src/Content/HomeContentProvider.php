<?php
/**
 * Resuelve el contenido de la portada (hero, última hora, más noticias) con una
 * única consulta a WP_Query, repartiendo los resultados por posición. Sin lógica de
 * negocio editorial (eso vive en dnorte-core si algún día hace falta destacar
 * manualmente un artículo) — por ahora, "más reciente primero" es la política.
 *
 * @package DNorteTheme\Content
 */

declare(strict_types=1);

namespace DNorteTheme\Content;

use WP_Post;
use WP_Query;

final class HomeContentProvider {

	private const BREAKING_COUNT = 3;
	private const LATEST_COUNT   = 6;

	/**
	 * @return array{hero: WP_Post|null, breaking: list<WP_Post>, latest: list<WP_Post>}
	 */
	public function content(): array {
		$total = 1 + self::BREAKING_COUNT + self::LATEST_COUNT;

		$query = new WP_Query(
			array(
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'posts_per_page'         => $total,
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => true,
			)
		);

		/** @var list<WP_Post> $posts */
		$posts = $query->posts;

		wp_reset_postdata();

		return array(
			'hero'     => $posts[0] ?? null,
			'breaking' => array_slice( $posts, 1, self::BREAKING_COUNT ),
			'latest'   => array_slice( $posts, 1 + self::BREAKING_COUNT, self::LATEST_COUNT ),
		);
	}
}
