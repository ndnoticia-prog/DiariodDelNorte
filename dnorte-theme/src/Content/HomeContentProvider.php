<?php
/**
 * Resuelve todo el contenido de la portada real (v0.1.0-alpha.18: hero de gran
 * formato + dos noticias secundarias, La Guajira, Judiciales, Opinión, Más
 * noticias, Lo más leído — con tres ventanas de tiempo — y Edición Impresa).
 * Varias WP_Query en vez de una sola: cada sección tiene su propio origen
 * (categoría distinta, o ranking por vistas en vez de fecha) — no hay forma de
 * resolverlas con una única consulta.
 *
 * Deduplicación deliberadamente parcial: "Más noticias" excluye los posts ya
 * usados en el grupo del hero (mismo fondo común de "más recientes", el
 * duplicado ahí se vería como un error de plantilla) pero los bloques de
 * categoría NO se excluyen entre sí ni contra el hero — un artículo puede ser
 * a la vez la noticia más comentada del momento (hero) y pertenecer a La
 * Guajira; eso no es un bug, es real en cualquier portada de diario.
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
use WP_Term;

final class HomeContentProvider {

	private const HERO_TOTAL        = 3; // El primero es el hero grande; los otros dos, secundarias.
	private const CATEGORY_FEATURED = 3;
	private const CATEGORY_LIST     = 6;
	private const JUDICIALES_COUNT  = 4;
	private const OPINION_COUNT     = 6;
	private const MOST_READ_COUNT   = 5;
	private const NEWS_GRID_COUNT   = 12;

	/**
	 * @return array{
	 *     hero: WP_Post|null,
	 *     heroSecondary: list<WP_Post>,
	 *     laGuajiraFeatured: list<WP_Post>,
	 *     laGuajiraList: list<WP_Post>,
	 *     judiciales: list<WP_Post>,
	 *     opinion: list<WP_Post>,
	 *     edicionImpresa: WP_Post|null,
	 *     edicionImpresaPdfUrl: string,
	 *     mostRead: array{'24h': list<WP_Post>, '7d': list<WP_Post>, '30d': list<WP_Post>},
	 *     newsGrid: list<WP_Post>,
	 *     newsGridExcluded: list<int>
	 * }
	 */
	public function content(): array {
		/** @var list<int> $used */
		$used = array();

		$heroGroup = $this->recentPosts( self::HERO_TOTAL, $used );

		// Snapshot justo aquí (antes de que newsGrid vuelva a extender $used con sus
		// propios ids): "Cargar más" (assets/js/app.js) necesita esta misma lista
		// para no repetir en la siguiente página lo que ya se ve en el hero.
		$heroIds = $used;

		$laGuajira   = $this->categoryPosts( 'la-guajira', self::CATEGORY_FEATURED + self::CATEGORY_LIST );
		$impresa     = $this->categoryPosts( 'edicion-impresa', 1 );
		$impresaPost = $impresa[0] ?? null;

		// "Más noticias" solo excluye lo ya mostrado en el hero (mismo fondo común
		// de "más recientes" sin categoría particular) — ver docblock.
		$newsGrid = $this->recentPosts( self::NEWS_GRID_COUNT, $used );

		return array(
			'hero'                 => $heroGroup[0] ?? null,
			'heroSecondary'        => array_slice( $heroGroup, 1 ),
			'laGuajiraFeatured'    => array_slice( $laGuajira, 0, self::CATEGORY_FEATURED ),
			'laGuajiraList'        => array_slice( $laGuajira, self::CATEGORY_FEATURED ),
			'judiciales'           => $this->categoryPosts( 'judiciales', self::JUDICIALES_COUNT ),
			'opinion'              => $this->categoryPosts( 'opinion', self::OPINION_COUNT ),
			'edicionImpresa'       => $impresaPost,
			'edicionImpresaPdfUrl' => $impresaPost !== null ? $this->firstAttachedPdfUrl( $impresaPost ) : '',
			'mostRead'             => $this->mostReadByWindow(),
			'newsGrid'             => $newsGrid,
			'newsGridExcluded'     => $heroIds,
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
				// "Edición impresa" no es un titular más: es la referencia de portada
				// del día (ver print-edition.php), que se publica con fecha muy
				// reciente cada vez — sin esto, terminaría colándose como hero o en
				// "Más noticias" simplemente por ser el post más nuevo, desplazando a
				// una noticia real. Bug real encontrado en la verificación en el
				// navegador con datos de ejemplo.
				'category__not_in'       => $this->edicionImpresaCategoryId(),
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
	 * @return list<int>
	 */
	private function edicionImpresaCategoryId(): array {
		$term = get_term_by( 'slug', 'edicion-impresa', 'category' );

		return $term instanceof WP_Term ? array( $term->term_id ) : array();
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
	 * "Descargar PDF" de Edición Impresa (template-parts/blocks/print-edition.php)
	 * reutiliza un adjunto real de la Biblioteca de medios subido al propio post
	 * (el primer PDF encontrado) — sin ningún campo/sistema nuevo de carga.
	 * Cadena vacía si no hay ninguno adjunto, el botón simplemente no se imprime.
	 */
	private function firstAttachedPdfUrl( WP_Post $post ): string {
		$attachments = get_attached_media( 'application/pdf', $post );

		if ( $attachments === array() ) {
			return '';
		}

		$first = reset( $attachments );
		$url   = wp_get_attachment_url( $first->ID );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Ranking real por vistas (Analytics\Pageviews\PageviewRepository, ya
	 * alimentado por el propio sitio) en tres ventanas — 24 horas/7 días/30
	 * días — para los filtros de "Lo más leído". Se calculan las tres de una
	 * vez en vez de bajo demanda por JS: evita sumar un endpoint REST nuevo
	 * solo para esto, las tres listas ya vienen en el HTML y el filtro en
	 * pantalla solo muestra/oculta (ver assets/js/app.js).
	 *
	 * @return array{'24h': list<WP_Post>, '7d': list<WP_Post>, '30d': list<WP_Post>}
	 */
	private function mostReadByWindow(): array {
		return array(
			'24h' => $this->mostReadSince( 1 ),
			'7d'  => $this->mostReadSince( 7 ),
			'30d' => $this->mostReadSince( 30 ),
		);
	}

	/**
	 * Si Analítica no está activa o todavía no acumuló suficientes vistas
	 * (sitio recién publicado), completa el resto con los artículos más
	 * recientes en su lugar — nunca deja la sección vacía ni a medias por
	 * falta de datos históricos.
	 *
	 * @return list<WP_Post>
	 */
	private function mostReadSince( int $days ): array {
		$posts = array();

		try {
			if ( class_exists( Application::class ) ) {
				/** @var PageviewRepository $repository */
				$repository = Application::instance()->container()->get( PageviewRepository::class );
				$since      = new DateTimeImmutable( sprintf( '-%d days', $days ), new DateTimeZone( 'UTC' ) );

				foreach ( $repository->topArticlesSince( $since, self::MOST_READ_COUNT ) as $row ) {
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

		if ( count( $posts ) < self::MOST_READ_COUNT ) {
			/** @var list<int> $alreadyUsed */
			$alreadyUsed = array_map( static fn ( WP_Post $post ): int => $post->ID, $posts );
			$fallback    = $this->recentPosts( self::MOST_READ_COUNT - count( $posts ), $alreadyUsed );
			$posts       = array( ...$posts, ...$fallback );
		}

		return $posts;
	}
}
