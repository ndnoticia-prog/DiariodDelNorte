<?php
/**
 * Purga diaria (WP-Cron, ver Providers\AnalyticsServiceProvider) de filas de
 * dnorte_pageviews más antiguas que `analytics.retention_days`.
 *
 * @package DNorteCore\Analytics
 */

declare(strict_types=1);

namespace DNorteCore\Analytics;

use DateTimeImmutable;
use DateTimeZone;
use DNorteCore\Analytics\Pageviews\PageviewRepository;
use DNorteCore\Config\Config;

final class PageviewPurger {

	public function __construct(
		private readonly PageviewRepository $repository,
		private readonly Config $config
	) {
	}

	public function purge(): void {
		/** @var int $retentionDays */
		$retentionDays = $this->config->get( 'analytics.retention_days', 90 );

		$cutoff = ( new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )
			->modify( "-{$retentionDays} days" );

		$this->repository->purgeOlderThan( $cutoff );
	}
}
