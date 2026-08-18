<?php
/**
 * @package DNorteTheme\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteTheme\Tests\Integration\Content;

use DNorteTheme\Content\HomeContentProvider;
use WP_UnitTestCase;

final class HomeContentProviderTest extends WP_UnitTestCase {

	public function test_content_returns_null_hero_and_empty_lists_without_posts(): void {
		$content = ( new HomeContentProvider() )->content();

		self::assertNull( $content['hero'] );
		self::assertSame( array(), $content['breaking'] );
		self::assertSame( array(), $content['latest'] );
	}

	public function test_content_puts_the_most_recent_post_as_hero(): void {
		self::factory()->post->create(
			array(
				'post_title' => 'Más antiguo',
				'post_date'  => '2020-01-01 00:00:00',
			)
		);
		$newestId = self::factory()->post->create(
			array(
				'post_title' => 'Más reciente',
				'post_date'  => '2024-01-01 00:00:00',
			)
		);

		$content = ( new HomeContentProvider() )->content();

		self::assertNotNull( $content['hero'] );
		self::assertSame( $newestId, $content['hero']->ID );
	}

	public function test_content_distributes_the_next_posts_between_breaking_and_latest(): void {
		self::factory()->post->create_many( 10 );

		$content = ( new HomeContentProvider() )->content();

		self::assertCount( 3, $content['breaking'] );
		self::assertCount( 6, $content['latest'] );

		$heroId      = $content['hero']->ID;
		$breakingIds = array_map( static fn ( $post ) => $post->ID, $content['breaking'] );
		$latestIds   = array_map( static fn ( $post ) => $post->ID, $content['latest'] );

		self::assertNotContains( $heroId, $breakingIds );
		self::assertNotContains( $heroId, $latestIds );
		self::assertEmpty( array_intersect( $breakingIds, $latestIds ) );
	}

	public function test_content_does_not_include_draft_posts(): void {
		self::factory()->post->create( array( 'post_status' => 'draft' ) );

		$content = ( new HomeContentProvider() )->content();

		self::assertNull( $content['hero'] );
	}
}
