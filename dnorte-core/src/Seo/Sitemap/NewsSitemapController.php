<?php
/**
 * Sitemap de Google News (`/sitemap-news.xml`) — WordPress core no lo provee (a
 * diferencia del sitemap general, `wp-sitemap.xml` desde 5.5, que sí reutilizamos sin
 * reimplementar): usa un espacio de nombres XML distinto (`news:`) y solo incluye
 * artículos de las últimas horas (configurable, `config/seo.php`). Mismo criterio que
 * ND Platform.
 *
 * @package DNorteCore\Seo\Sitemap
 */

declare(strict_types=1);

namespace DNorteCore\Seo\Sitemap;

use WP_Post;
use WP_Query;
use XMLWriter;

final class NewsSitemapController {

	// Límite real de Google News: un sitemap de noticias no debe superar 1000 URLs.
	private const MAX_URLS = 1000;

	public function __construct(
		private readonly string $siteName,
		private readonly string $language,
		private readonly int $windowHours
	) {
	}

	public function registerRewriteRule(): void {
		add_rewrite_rule( '^sitemap-news\.xml$', 'index.php?dnorte_sitemap_news=1', 'top' );
	}

	/**
	 * @param list<string> $vars
	 * @return list<string>
	 */
	public function registerQueryVar( array $vars ): array {
		$vars[] = 'dnorte_sitemap_news';

		return $vars;
	}

	public function maybeRender( WP_Query $query ): void {
		if ( ! $query->is_main_query() || ! (bool) $query->get( 'dnorte_sitemap_news' ) ) {
			return;
		}

		header( 'Content-Type: application/xml; charset=UTF-8' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render() ya escapa cada valor con XMLWriter (no es HTML, esc_html() corrompería el XML).
		echo $this->render( $this->recentArticleData() );
		exit;
	}

	/**
	 * Construye el XML a partir de datos ya resueltos (sin tocar WP_Post) — así se
	 * puede probar con Brain Monkey, sin necesidad de un WordPress real. Ver
	 * recentArticleData() para la parte que sí depende de WP_Query/WP_Post
	 * (cubierta con pruebas de integración, no unitarias).
	 *
	 * @param list<array{url: string, title: string, published_at: string}> $articles
	 */
	public function render( array $articles ): string {
		$writer = new XMLWriter();
		$writer->openMemory();
		$writer->setIndent( true );
		$writer->startDocument( '1.0', 'UTF-8' );

		$writer->startElement( 'urlset' );
		$writer->writeAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );
		$writer->writeAttribute( 'xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9' );

		foreach ( $articles as $article ) {
			$writer->startElement( 'url' );
			$writer->writeElement( 'loc', $article['url'] );

			$writer->startElement( 'news:news' );
			$writer->startElement( 'news:publication' );
			$writer->writeElement( 'news:name', $this->siteName );
			$writer->writeElement( 'news:language', $this->language );
			$writer->endElement(); // news:publication
			$writer->writeElement( 'news:publication_date', $article['published_at'] );
			$writer->writeElement( 'news:title', $article['title'] );
			$writer->endElement(); // news:news

			$writer->endElement(); // url
		}

		$writer->endElement(); // urlset
		$writer->endDocument();

		return $writer->outputMemory();
	}

	/**
	 * @return list<array{url: string, title: string, published_at: string}>
	 */
	public function recentArticleData(): array {
		return array_map(
			fn ( WP_Post $post ): array => array(
				'url'          => (string) get_permalink( $post ),
				'title'        => get_the_title( $post ),
				'published_at' => $this->publishedAt( $post ),
			),
			$this->recentArticles()
		);
	}

	/**
	 * get_the_date() puede devolver false si el post no tiene fecha — no ocurre en la
	 * práctica para artículos publicados reales, pero el tipo lo permite. En ese caso
	 * (nunca visto, solo por completitud de tipos) se recurre directamente a
	 * post_date_gmt.
	 */
	private function publishedAt( WP_Post $post ): string {
		$date = get_the_date( DATE_W3C, $post );

		if ( is_string( $date ) ) {
			return $date;
		}

		$timestamp = strtotime( $post->post_date_gmt . ' UTC' );

		return $timestamp !== false ? gmdate( DATE_W3C, $timestamp ) : '';
	}

	/**
	 * @return list<WP_Post>
	 */
	private function recentArticles(): array {
		$since = gmdate( 'Y-m-d H:i:s', time() - $this->windowHours * HOUR_IN_SECONDS );

		$query = new WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => self::MAX_URLS,
				'no_found_rows'  => true,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'date_query'     => array(
					array(
						'column' => 'post_date_gmt',
						'after'  => $since,
					),
				),
			)
		);

		/** @var list<WP_Post> $posts */
		$posts = $query->posts;

		return $posts;
	}
}
