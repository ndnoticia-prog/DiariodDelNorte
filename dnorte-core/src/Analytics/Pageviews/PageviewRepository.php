<?php
/**
 * @package DNorteCore\Analytics\Pageviews
 */

declare(strict_types=1);

namespace DNorteCore\Analytics\Pageviews;

use DateTimeImmutable;
use DNorteCore\Database\DatabaseManager;

final class PageviewRepository {

	public function __construct( private readonly DatabaseManager $database ) {
	}

	public function record( int $postId, ?string $referrerHost ): void {
		$this->database->insert(
			$this->database->table( 'pageviews' ),
			array(
				'post_id'       => $postId,
				'referrer_host' => $referrerHost ?? '',
				'viewed_at'     => gmdate( 'Y-m-d H:i:s' ),
			)
		);
	}

	public function totalSince( DateTimeImmutable $since ): int {
		$table = $this->database->table( 'pageviews' );

		$row = $this->database->selectOne(
			"SELECT COUNT(*) as total FROM {$table} WHERE viewed_at >= %s",
			array( $since->format( 'Y-m-d H:i:s' ) )
		);

		return $row !== null ? (int) $row['total'] : 0;
	}

	/**
	 * Artículos más vistos desde $since, de más a menos vistas.
	 *
	 * @return list<array{post_id: int, views: int}>
	 */
	public function topArticlesSince( DateTimeImmutable $since, int $limit = 10 ): array {
		$table = $this->database->table( 'pageviews' );

		$rows = $this->database->select(
			"SELECT post_id, COUNT(*) as views FROM {$table} WHERE viewed_at >= %s GROUP BY post_id ORDER BY views DESC LIMIT %d",
			array( $since->format( 'Y-m-d H:i:s' ), $limit )
		);

		return array_map(
			static fn ( array $row ): array => array(
				'post_id' => (int) $row['post_id'],
				'views'   => (int) $row['views'],
			),
			$rows
		);
	}

	public function purgeOlderThan( DateTimeImmutable $cutoff ): void {
		$table = $this->database->table( 'pageviews' );

		$this->database->statement(
			"DELETE FROM {$table} WHERE viewed_at < %s",
			array( $cutoff->format( 'Y-m-d H:i:s' ) )
		);
	}
}
