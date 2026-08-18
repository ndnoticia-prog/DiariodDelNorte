<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Seo\Context;

use DNorteCore\Seo\Context\SeoContextResolver;
use WP_UnitTestCase;

final class SeoContextResolverTest extends WP_UnitTestCase {

	public function test_resolve_returns_article_context_for_a_published_post(): void {
		$postId = self::factory()->post->create(
			array(
				'post_title'   => 'Un artículo de prueba',
				'post_excerpt' => 'Un resumen breve.',
			)
		);

		$this->go_to( get_permalink( $postId ) );

		$context = ( new SeoContextResolver() )->resolve();

		self::assertSame( 'Un artículo de prueba', $context->title );
		self::assertSame( 'Un resumen breve.', $context->description );
		self::assertSame( 'article', $context->ogType );
		self::assertTrue( $context->isArticle() );
		self::assertNotNull( $context->post );
		self::assertSame( $postId, $context->post->ID );
		self::assertFalse( $context->noindex );
	}

	public function test_resolve_returns_website_context_for_the_home_page(): void {
		$this->go_to( home_url( '/' ) );

		$context = ( new SeoContextResolver() )->resolve();

		self::assertSame( 'website', $context->ogType );
		self::assertFalse( $context->noindex );
		self::assertNull( $context->post );
	}

	public function test_resolve_marks_search_results_as_noindex(): void {
		$this->go_to( home_url( '/?s=prueba' ) );

		$context = ( new SeoContextResolver() )->resolve();

		self::assertTrue( $context->noindex );
	}

	public function test_resolve_marks_a_404_as_noindex(): void {
		$this->go_to( home_url( '/?p=999999999' ) );

		$context = ( new SeoContextResolver() )->resolve();

		self::assertTrue( $context->noindex );
	}

	public function test_resolve_uses_the_featured_image_when_the_post_has_one(): void {
		$attachmentId = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$postId       = self::factory()->post->create( array( 'post_title' => 'Con imagen destacada' ) );
		set_post_thumbnail( $postId, $attachmentId );

		$this->go_to( get_permalink( $postId ) );

		$context = ( new SeoContextResolver() )->resolve();

		self::assertNotNull( $context->imageUrl );
	}
}
