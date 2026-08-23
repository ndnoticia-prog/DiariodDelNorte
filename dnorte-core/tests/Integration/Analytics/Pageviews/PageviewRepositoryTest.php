<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Analytics\Pageviews;

use DateTimeImmutable;
use DateTimeZone;
use DNorteCore\Analytics\Pageviews\PageviewRepository;
use DNorteCore\Database\DatabaseManager;
use WP_UnitTestCase;

final class PageviewRepositoryTest extends WP_UnitTestCase {

	public function test_total_since_only_counts_views_within_the_window(): void {
		global $wpdb;
		$repository = new PageviewRepository( new DatabaseManager( $wpdb ) );

		$now = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		$repository->record( 1, 'example.com' );
		$this->backdatePageview( 1, $now->modify( '-40 days' ) );

		$repository->record( 1, null );

		self::assertSame( 1, $repository->totalSince( $now->modify( '-1 day' ) ) );
		self::assertSame( 2, $repository->totalSince( $now->modify( '-50 days' ) ) );
	}

	public function test_top_articles_since_ranks_by_view_count_descending(): void {
		global $wpdb;
		$repository = new PageviewRepository( new DatabaseManager( $wpdb ) );

		$now = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		$repository->record( 10, null );
		$repository->record( 20, null );
		$repository->record( 20, null );
		$repository->record( 20, null );

		$top = $repository->topArticlesSince( $now->modify( '-1 hour' ), 10 );

		self::assertSame( 20, $top[0]['post_id'] );
		self::assertSame( 3, $top[0]['views'] );
		self::assertSame( 10, $top[1]['post_id'] );
		self::assertSame( 1, $top[1]['views'] );
	}

	public function test_purge_older_than_removes_only_rows_before_the_cutoff(): void {
		global $wpdb;
		$repository = new PageviewRepository( new DatabaseManager( $wpdb ) );

		$now = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		$repository->record( 99, null );
		$this->backdatePageview( 99, $now->modify( '-100 days' ) );
		$repository->record( 99, null );

		$repository->purgeOlderThan( $now->modify( '-90 days' ) );

		self::assertSame( 1, $repository->totalSince( $now->modify( '-1 day' ) ) );
	}

	/**
	 * PageviewRepository::record() siempre usa "ahora" — para probar ventanas de
	 * tiempo hace falta poder colocar una fila en el pasado a mano.
	 */
	private function backdatePageview( int $postId, DateTimeImmutable $viewedAt ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}dnorte_pageviews SET viewed_at = %s WHERE post_id = %d ORDER BY id DESC LIMIT 1",
				$viewedAt->format( 'Y-m-d H:i:s' ),
				$postId
			)
		);
	}
}
