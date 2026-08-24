<?php
/**
 * Resuelve todo el contenido de la portada real pedida por el cliente: el grupo
 * "Lo último" (hero + tira de miniaturas + columna de tarjetas), los bloques de
 * categoría (La Guajira, Judiciales), Opinión, Editorial + Edición Impresa, Lo
 * más leído (vía Analytics\Pageviews\PageviewRepository, ya existente) y la
 * cuadrícula final "Más noticias". Varias WP_Query en vez de una sola: cada
 * sección tiene su propio origen (categoría distinta, o ranking por vistas en
 * vez de fecha) — no hay forma de resolverlas con una única consulta como hacía
 * la versión anterior de esta clase.
 *
 * Deduplicación deliberadamente parcial: "Más noticias" excluye los posts ya
 * usados en "Lo último" (mismo fondo común de "más recientes", el duplicado ahí
 * se vería como un error de plantilla) pero los bloques de categoría NO se
 * excluyen entre sí ni contra el hero — un artículo puede ser a la vez la
 * noticia más comentada del momento (hero) y pertenecer a La Guajira; eso no es
 * un bug, es real en cualquier portada de diario.
 *
 * @package DNorteTheme\Content
 */

declare(strict_types=1);

namespace DNorteTheme\Content;

use DateTimeImmutable;
use DateTimeZone;
use DNorteCore\Analytics\Pageviews\PageviewRepository;
use DNorteCore\Application;
use WP_Post;
use WP_Query;

final class HomeContentProvider {

	private const HERO_TOTAL        = 7; // El primero es el hero grande; el resto, miniaturas.
	private const ASIDE_COUNT       = 6;
	private const CATEGORY_FEATURED = 3;
	private const CATEGORY_LIST     = 6;
	private const JUDICIALES_COUNT  = 9;
	private const OPINION_COUNT     = 6;
	private const MOST_READ_COUNT   = 4;
	private const MOST_READ_DAYS    = 7;
	private const NEWS_GRID_COUNT   = 16;

	/**
	 * @return array{
	 *     hero: WP_Post|null,
	 *     heroThumbs: list<WP_Post>,
	 *     aside: list<WP_Post>,
	 *     laGuajiraFeatured: list<WP_Post>,
	 *     laGuajiraList: list<WP_Post>,
	 *     judiciales: list<WP_Post>,
	 *     opinion: list<WP_Post>,
	 *     editorial: WP_Post|null,
	 *     edicionImpresa: WP_Post|null,
	 *     mostRead: list<WP_Post>,
	 *     newsGrid: list<WP_Post>,
	 *     newsGridExcluded: list<int>
	 * }
	 */
	public function content(): array {
		/** @var list<int> $used */
		$used = array();

		$heroGroup = $this->recentPosts( self::HERO_TOTAL, $used );
		$aside     = $this->recentPosts( self::ASIDE_COUNT, $used );

		// Snapshot justo aquí (antes de que newsGrid vuelva a extender $used con sus
		// propios ids): "Cargar más" (assets/js/app.js) necesita esta misma lista
		// para no repetir en la siguiente página lo que ya se ve en "Lo último".
		$heroAndAsideIds = $used;

		$laGuajira = $this->categoryPosts( 'la-guajira', self::CATEGORY_FEATURED + self::CATEGORY_LIST );
		$editorial = $this->categoryPosts( 'editorial', 1 );
		$impresa   = $this->categoryPosts( 'edicion-impresa', 1 );

		// "Más noticias" solo excluye lo ya mostrado en "Lo último" (mismo fondo
		// común de "más recientes" sin categoría particular) — ver docblock.
		$newsGrid = $this->recentPosts( self::NEWS_GRID_COUNT, $used );

		return array(
			'hero'              => $heroGroup[0] ?? null,
			'heroThumbs'        => array_slice( $heroGroup, 1 ),
			'aside'             => $aside,
			'laGuajiraFeatured' => array_slice( $laGuajira, 0, self::CATEGORY_FEATURED ),
			'laGuajiraList'     => array_slice( $laGuajira, self::CATEGORY_FEATURED ),
			'judiciales'        => $this->categoryPosts( 'judiciales', self::JUDICIALES_COUNT ),
			'opinion'           => $this->categoryPosts( 'opinion', self::OPINION_COUNT ),
			'editorial'         => $editorial[0] ?? null,
			'edicionImpresa'    => $impresa[0] ?? null,
			'mostRead'          => $this->mostRead( self::MOST_READ_COUNT ),
			'newsGrid'          => $newsGrid,
			'newsGridExcluded'  => $heroAndAsideIds,
		);
	}

	/**
	 * @param list<int> $usedIds Ids ya repartidos a otra sección — se excluyen de
	 *                            esta consulta y se amplían con los que devuelve.
	 * @return list<WP_Post>
	 */
	private function recentPosts( int $count, array &$usedIds ): array {
		$query = new WP_Query(
			array(
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'posts_per_page'         => $count,
				'post__not_in'           => $usedIds,
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
			)
		);

		/** @var list<WP_Post> $posts */
		$posts = $query->posts;
		wp_reset_postdata();

		foreach ( $posts as $post ) {
			$usedIds[] = $post->ID;
		}

		return $posts;
	}

	/**
	 * @return list<WP_Post>
	 */
	private function categoryPosts( string $categorySlug, int $count ): array {
		$query = new WP_Query(
			array(
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'posts_per_page'         => $count,
				'category_name'          => $categorySlug,
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
			)
		);

		/** @var list<WP_Post> $posts */
		$posts = $query->posts;
		wp_reset_postdata();

		return $posts;
	}

	/**
	 * Ranking real por vistas de los últimos `MOST_READ_DAYS` días
	 * (Analytics\Pageviews\PageviewRepository, ya alimentado por el propio sitio —
	 * ver Analytics\PageviewBeaconRenderer). Si Analítica no está activa o
	 * todavía no acumuló suficientes vistas (sitio recién publicado), completa el
	 * resto con los artículos más recientes en su lugar — nunca deja la sección
	 * vacía ni a medias por falta de datos históricos.
	 *
	 * @return list<WP_Post>
	 */
	private function mostRead( int $count ): array {
		$posts = array();

		try {
			if ( class_exists( Application::class ) ) {
				/** @var PageviewRepository $repository */
				$repository = Application::instance()->container()->get( PageviewRepository::class );
				$since      = new DateTimeImmutable( sprintf( '-%d days', self::MOST_READ_DAYS ), new DateTimeZone( 'UTC' ) );

				foreach ( $repository->topArticlesSince( $since, $count ) as $row ) {
					$post = get_post( $row['post_id'] );

					if ( $post instanceof WP_Post && $post->post_status === 'publish' ) {
						$posts[] = $post;
					}
				}
			}
		} catch ( \Throwable $exception ) {
			// La aplicación de dnorte-core no llegó a arrancar en este proceso (ej. las
			// pruebas de integración de dnorte-theme no cargan dnorte-core.php, ver
			// tests/Integration/bootstrap.php) o Analítica no está activa — cae al
			// respaldo de abajo en vez de romper la portada por esto.
			$posts = array();
		}

		if ( count( $posts ) < $count ) {
			/** @var list<int> $alreadyUsed */
			$alreadyUsed = array_map( static fn ( WP_Post $post ): int => $post->ID, $posts );
			$fallback    = $this->recentPosts( $count - count( $posts ), $alreadyUsed );
			$posts       = array( ...$posts, ...$fallback );
		}

		return $posts;
	}
}
