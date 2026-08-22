<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Seo\Sitemap;

use DNorteCore\Seo\Sitemap\NewsSitemapController;
use WP_UnitTestCase;

final class NewsSitemapControllerTest extends WP_UnitTestCase {

	public function test_recent_article_data_only_includes_posts_within_the_time_window(): void {
		$insideId = self::factory()->post->create(
			array(
				'post_title'    => 'Dentro de la ventana',
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - 2 * HOUR_IN_SECONDS ),
				'post_date'     => gmdate( 'Y-m-d H:i:s', time() - 2 * HOUR_IN_SECONDS ),
			)
		);
		self::factory()->post->create(
			array(
				'post_title'    => 'Fuera de la ventana',
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - 72 * HOUR_IN_SECONDS ),
				'post_date'     => gmdate( 'Y-m-d H:i:s', time() - 72 * HOUR_IN_SECONDS ),
			)
		);

		$controller = new NewsSitemapController( 'Diario del Norte', 'es', 48 );
		$data       = $controller->recentArticleData();

		$urls = array_column( $data, 'url' );

		self::assertContains( get_permalink( $insideId ), $urls );
		self::assertCount( 1, $data );
	}

	public function test_recent_article_data_excludes_draft_posts(): void {
		self::factory()->post->create(
			array(
				'post_status'   => 'draft',
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
			)
		);

		$controller = new NewsSitemapController( 'Diario del Norte', 'es', 48 );

		self::assertSame( array(), $controller->recentArticleData() );
	}

	public function test_recent_article_data_orders_newest_first(): void {
		$olderId = self::factory()->post->create(
			array(
				'post_title'    => 'Más antiguo',
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - 10 * HOUR_IN_SECONDS ),
				'post_date'     => gmdate( 'Y-m-d H:i:s', time() - 10 * HOUR_IN_SECONDS ),
			)
		);
		$newerId = self::factory()->post->create(
			array(
				'post_title'    => 'Más reciente',
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
				'post_date'     => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
			)
		);

		$controller = new NewsSitemapController( 'Diario del Norte', 'es', 48 );
		$data       = $controller->recentArticleData();

		self::assertSame( get_permalink( $newerId ), $data[0]['url'] );
		self::assertSame( get_permalink( $olderId ), $data[1]['url'] );
	}

	public function test_render_end_to_end_produces_parseable_xml_from_real_posts(): void {
		self::factory()->post->create(
			array(
				'post_title'    => 'Artículo real',
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
				'post_date'     => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
			)
		);

		$controller = new NewsSitemapController( 'Diario del Norte', 'es', 48 );
		$xml        = $controller->render( $controller->recentArticleData() );

		$doc = simplexml_load_string( $xml );

		self::assertNotFalse( $doc );
		self::assertCount( 1, $doc->url );
		self::assertSame( 'Artículo real', (string) $doc->url[0]->children( 'http://www.google.com/schemas/sitemap-news/0.9' )->news->title );
	}
}
