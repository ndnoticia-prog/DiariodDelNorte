<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Analytics;

use DateTimeImmutable;
use DateTimeZone;
use DNorteCore\Analytics\PageviewPurger;
use DNorteCore\Analytics\Pageviews\PageviewRepository;
use DNorteCore\Config\Config;
use DNorteCore\Database\DatabaseManager;
use WP_UnitTestCase;

final class PageviewPurgerTest extends WP_UnitTestCase {

	public function test_purge_respects_the_configured_retention_window(): void {
		global $wpdb;
		$repository = new PageviewRepository( new DatabaseManager( $wpdb ) );
		$purger     = new PageviewPurger(
			$repository,
			new Config( array( 'analytics' => array( 'retention_days' => 30 ) ) )
		);

		$now = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		$repository->record( 7, null );
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}dnorte_pageviews SET viewed_at = %s WHERE post_id = %d",
				$now->modify( '-45 days' )->format( 'Y-m-d H:i:s' ),
				7
			)
		);
		$repository->record( 7, null ); // Dentro de la ventana de retención.

		$purger->purge();

		self::assertSame( 1, $repository->totalSince( $now->modify( '-1 hour' ) ) );
		self::assertSame( 1, $repository->totalSince( $now->modify( '-60 days' ) ) );
	}
}
