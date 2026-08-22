<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Seo\Sitemap;

use DNorteCore\Seo\Sitemap\NewsSitemapController;
use DNorteCore\Tests\Unit\TestCase;

final class NewsSitemapControllerTest extends TestCase {

	public function test_render_returns_a_valid_urlset_without_articles(): void {
		$controller = new NewsSitemapController( 'Diario del Norte', 'es', 48 );

		$xml = $controller->render( array() );

		$doc = simplexml_load_string( $xml );
		self::assertNotFalse( $doc );
		self::assertCount( 0, $doc->url );
	}

	public function test_render_includes_the_news_namespace_and_publication_data(): void {
		$controller = new NewsSitemapController( 'Diario del Norte', 'es', 48 );

		$xml = $controller->render(
			array(
				array(
					'url'          => 'https://diariodelnorte.net/un-articulo/',
					'title'        => 'Un artículo de prueba',
					'published_at' => '2026-08-18T10:00:00+00:00',
				),
			)
		);

		self::assertStringContainsString( 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"', $xml );

		$doc = simplexml_load_string( $xml );
		self::assertNotFalse( $doc );
		self::assertCount( 1, $doc->url );

		$news = $doc->url[0]->children( 'http://www.google.com/schemas/sitemap-news/0.9' )->news;

		self::assertSame( 'https://diariodelnorte.net/un-articulo/', (string) $doc->url[0]->loc );
		self::assertSame( 'Diario del Norte', (string) $news->publication->name );
		self::assertSame( 'es', (string) $news->publication->language );
		self::assertSame( '2026-08-18T10:00:00+00:00', (string) $news->publication_date );
		self::assertSame( 'Un artículo de prueba', (string) $news->title );
	}

	public function test_render_escapes_a_title_that_looks_like_markup(): void {
		$controller = new NewsSitemapController( 'Diario del Norte', 'es', 48 );

		$xml = $controller->render(
			array(
				array(
					'url'          => 'https://diariodelnorte.net/otro/',
					'title'        => 'Título con <script>alert(1)</script> y & ampersand',
					'published_at' => '2026-08-18T10:00:00+00:00',
				),
			)
		);

		$doc = simplexml_load_string( $xml );
		self::assertNotFalse( $doc );

		$news = $doc->url[0]->children( 'http://www.google.com/schemas/sitemap-news/0.9' )->news;

		self::assertSame( 'Título con <script>alert(1)</script> y & ampersand', (string) $news->title );
		self::assertStringNotContainsString( '<script>alert(1)</script>', $xml );
	}

	public function test_render_lists_multiple_articles_in_the_given_order(): void {
		$controller = new NewsSitemapController( 'Diario del Norte', 'es', 48 );

		$xml = $controller->render(
			array(
				array(
					'url'          => 'https://diariodelnorte.net/uno/',
					'title'        => 'Uno',
					'published_at' => '2026-08-18T10:00:00+00:00',
				),
				array(
					'url'          => 'https://diariodelnorte.net/dos/',
					'title'        => 'Dos',
					'published_at' => '2026-08-18T09:00:00+00:00',
				),
			)
		);

		$doc = simplexml_load_string( $xml );
		self::assertNotFalse( $doc );
		self::assertCount( 2, $doc->url );
		self::assertSame( 'https://diariodelnorte.net/uno/', (string) $doc->url[0]->loc );
		self::assertSame( 'https://diariodelnorte.net/dos/', (string) $doc->url[1]->loc );
	}
}
