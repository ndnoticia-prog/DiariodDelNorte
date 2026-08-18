<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Seo\Breadcrumbs;

use DNorteCore\Seo\Breadcrumbs\BreadcrumbBuilder;
use WP_UnitTestCase;

final class BreadcrumbBuilderTest extends WP_UnitTestCase {

	public function test_build_includes_site_category_and_post_for_a_singular_post(): void {
		$categoryId = self::factory()->category->create( array( 'name' => 'Nacional' ) );
		$postId     = self::factory()->post->create(
			array(
				'post_title'    => 'Un artículo',
				'post_category' => array( $categoryId ),
			)
		);

		$this->go_to( get_permalink( $postId ) );

		$items = ( new BreadcrumbBuilder( 'Diario del Norte', 'https://example.test/' ) )->build();

		self::assertCount( 3, $items );
		self::assertSame( 'Diario del Norte', $items[0]['name'] );
		self::assertSame( 'Nacional', $items[1]['name'] );
		self::assertSame( 'Un artículo', $items[2]['name'] );
	}

	public function test_build_returns_only_the_site_root_for_the_home_page(): void {
		$this->go_to( home_url( '/' ) );

		$items = ( new BreadcrumbBuilder( 'Diario del Norte', 'https://example.test/' ) )->build();

		self::assertCount( 1, $items );
		self::assertSame( 'Diario del Norte', $items[0]['name'] );
	}

	public function test_build_includes_the_category_archive_title(): void {
		$categoryId = self::factory()->category->create( array( 'name' => 'Deportes' ) );

		$this->go_to( get_category_link( $categoryId ) );

		$items = ( new BreadcrumbBuilder( 'Diario del Norte', 'https://example.test/' ) )->build();

		self::assertCount( 2, $items );
		self::assertSame( 'Deportes', $items[1]['name'] );
	}
}
