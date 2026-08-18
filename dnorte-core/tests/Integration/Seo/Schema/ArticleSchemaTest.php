<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Seo\Schema;

use DNorteCore\Seo\Schema\ArticleSchema;
use WP_UnitTestCase;

final class ArticleSchemaTest extends WP_UnitTestCase {

	public function test_build_returns_the_expected_news_article_shape(): void {
		$userId = self::factory()->user->create( array( 'display_name' => 'Redacción' ) );
		$postId = self::factory()->post->create(
			array(
				'post_title'  => 'Un artículo',
				'post_author' => $userId,
			)
		);
		$post   = get_post( $postId );

		self::assertNotNull( $post );

		$node = ( new ArticleSchema( 'https://example.test/' ) )->build( $post );

		self::assertSame( 'NewsArticle', $node['@type'] );
		self::assertSame( 'Un artículo', $node['headline'] );
		self::assertSame( 'Redacción', $node['author']['name'] );
		self::assertSame( array( '@id' => 'https://example.test/#organization' ), $node['publisher'] );
		self::assertSame( (string) get_permalink( $post ), $node['mainEntityOfPage']['@id'] );
	}
}
