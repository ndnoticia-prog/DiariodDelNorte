<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Ads;

use DateTimeImmutable;
use DateTimeZone;
use DNorteCore\Ads\Campaign;
use DNorteCore\Tests\Unit\TestCase;

final class CampaignTest extends TestCase {

	public function test_a_disabled_campaign_is_never_active(): void {
		$campaign = $this->makeCampaign( enabled: false );

		self::assertFalse( $campaign->isActiveAt( $this->now() ) );
	}

	public function test_an_enabled_campaign_without_dates_is_always_active(): void {
		$campaign = $this->makeCampaign();

		self::assertTrue( $campaign->isActiveAt( $this->now() ) );
	}

	public function test_a_campaign_is_inactive_before_its_start_date(): void {
		$campaign = $this->makeCampaign( startsAt: '2026-09-01 00:00:00' );

		self::assertFalse( $campaign->isActiveAt( $this->now() ) );
	}

	public function test_a_campaign_is_inactive_after_its_end_date(): void {
		$campaign = $this->makeCampaign( endsAt: '2026-08-01 00:00:00' );

		self::assertFalse( $campaign->isActiveAt( $this->now() ) );
	}

	public function test_applies_to_zone_checks_membership_in_the_zones_list(): void {
		$campaign = $this->makeCampaign( zones: array( 'cabecera', 'inicio' ) );

		self::assertTrue( $campaign->appliesToZone( 'cabecera' ) );
		self::assertFalse( $campaign->appliesToZone( 'final' ) );
	}

	public function test_a_campaign_without_categories_applies_to_any_category_context(): void {
		$campaign = $this->makeCampaign( categories: array() );

		self::assertTrue( $campaign->appliesToCategories( array( 'deportes' ) ) );
		self::assertTrue( $campaign->appliesToCategories( array() ) );
	}

	public function test_a_campaign_with_categories_only_applies_when_they_intersect(): void {
		$campaign = $this->makeCampaign( categories: array( 'deportes', 'economia' ) );

		self::assertTrue( $campaign->appliesToCategories( array( 'deportes' ) ) );
		self::assertFalse( $campaign->appliesToCategories( array( 'opinion' ) ) );
	}

	/**
	 * Ver el docblock de Campaign::appliesToCategories(): una campaña con
	 * categorías configuradas nunca aparece donde no hay contexto de categoría
	 * (los espacios sitewide Cabecera/Inicio).
	 */
	public function test_a_campaign_with_categories_never_applies_to_an_empty_context(): void {
		$campaign = $this->makeCampaign( categories: array( 'deportes' ) );

		self::assertFalse( $campaign->appliesToCategories( array() ) );
	}

	/**
	 * @param list<string> $zones
	 * @param list<string> $categories
	 */
	private function makeCampaign(
		bool $enabled = true,
		array $zones = array( 'cabecera' ),
		array $categories = array(),
		?string $startsAt = null,
		?string $endsAt = null
	): Campaign {
		return new Campaign( 1, 'Campaña de prueba', 'Anunciante', Campaign::TYPE_HTML, $enabled, 0, $zones, $categories, $startsAt, $endsAt, '<div>x</div>', '', '' );
	}

	private function now(): DateTimeImmutable {
		return new DateTimeImmutable( '2026-08-23 12:00:00', new DateTimeZone( 'UTC' ) );
	}
}
