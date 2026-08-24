<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Ads;

use DateTimeImmutable;
use DateTimeZone;
use DNorteCore\Ads\Campaign;
use DNorteCore\Ads\CampaignRepository;
use DNorteCore\Database\DatabaseManager;
use WP_UnitTestCase;

final class CampaignRepositoryTest extends WP_UnitTestCase {

	public function test_save_creates_a_new_campaign_when_id_is_zero(): void {
		global $wpdb;
		$repository = new CampaignRepository( new DatabaseManager( $wpdb ) );

		$id = $repository->save( $this->draft( zones: array( 'cabecera', 'inicio' ) ) );

		$saved = $repository->find( $id );

		self::assertNotNull( $saved );
		self::assertSame( 'Campaña de prueba', $saved->name );
		self::assertSame( array( 'cabecera', 'inicio' ), $saved->zones );
	}

	public function test_save_replaces_the_same_row_when_id_is_not_zero(): void {
		global $wpdb;
		$repository = new CampaignRepository( new DatabaseManager( $wpdb ) );

		$id = $repository->save( $this->draft() );

		$updated = $this->draft( id: $id, name: 'Campaña actualizada', priority: 5 );
		$repository->save( $updated );

		self::assertCount( 1, $repository->all() );

		$saved = $repository->find( $id );
		self::assertNotNull( $saved );
		self::assertSame( 'Campaña actualizada', $saved->name );
		self::assertSame( 5, $saved->priority );
	}

	public function test_delete_removes_the_campaign(): void {
		global $wpdb;
		$repository = new CampaignRepository( new DatabaseManager( $wpdb ) );

		$id = $repository->save( $this->draft() );
		$repository->delete( $id );

		self::assertNull( $repository->find( $id ) );
	}

	public function test_for_zone_picks_the_highest_priority_active_campaign_targeting_that_zone(): void {
		global $wpdb;
		$repository = new CampaignRepository( new DatabaseManager( $wpdb ) );

		$repository->save( $this->draft( name: 'Baja prioridad', zones: array( 'cabecera' ), priority: 1 ) );
		$repository->save( $this->draft( name: 'Alta prioridad', zones: array( 'cabecera' ), priority: 10 ) );
		$repository->save( $this->draft( name: 'Otra zona', zones: array( 'final' ), priority: 99 ) );

		$winner = $repository->forZone( 'cabecera', $this->now() );

		self::assertNotNull( $winner );
		self::assertSame( 'Alta prioridad', $winner->name );
	}

	public function test_for_zone_ignores_disabled_and_out_of_window_campaigns(): void {
		global $wpdb;
		$repository = new CampaignRepository( new DatabaseManager( $wpdb ) );

		$repository->save( $this->draft( name: 'Desactivada', zones: array( 'cabecera' ), enabled: false ) );
		$repository->save( $this->draft( name: 'Todavía no empieza', zones: array( 'cabecera' ), startsAt: '2027-01-01 00:00:00' ) );

		self::assertNull( $repository->forZone( 'cabecera', $this->now() ) );
	}

	public function test_for_zone_respects_category_targeting(): void {
		global $wpdb;
		$repository = new CampaignRepository( new DatabaseManager( $wpdb ) );

		$repository->save( $this->draft( name: 'Solo deportes', zones: array( 'top_noticia' ), categories: array( 'deportes' ) ) );

		self::assertNull( $repository->forZone( 'top_noticia', $this->now(), array( 'economia' ) ) );

		$winner = $repository->forZone( 'top_noticia', $this->now(), array( 'deportes' ) );
		self::assertNotNull( $winner );
		self::assertSame( 'Solo deportes', $winner->name );
	}

	public function test_record_impression_and_click_increment_atomically(): void {
		global $wpdb;
		$repository = new CampaignRepository( new DatabaseManager( $wpdb ) );

		$id = $repository->save( $this->draft() );

		$repository->recordImpression( $id );
		$repository->recordImpression( $id );
		$repository->recordImpression( $id );
		$repository->recordClick( $id );

		$saved = $repository->find( $id );

		self::assertNotNull( $saved );
		self::assertSame( 3, $saved->impressions );
		self::assertSame( 1, $saved->clicks );
	}

	public function test_add_evidence_appends_without_duplicating(): void {
		global $wpdb;
		$repository = new CampaignRepository( new DatabaseManager( $wpdb ) );

		$id = $repository->save( $this->draft() );

		$repository->addEvidence( $id, 501 );
		$repository->addEvidence( $id, 502 );
		$repository->addEvidence( $id, 501 ); // Duplicado, no debe repetirse.

		$saved = $repository->find( $id );

		self::assertNotNull( $saved );
		self::assertSame( array( 501, 502 ), $saved->evidenceIds );
	}

	/**
	 * @param list<string> $zones
	 * @param list<string> $categories
	 */
	private function draft(
		int $id = 0,
		string $name = 'Campaña de prueba',
		array $zones = array( 'cabecera' ),
		array $categories = array(),
		bool $enabled = true,
		int $priority = 0,
		?string $startsAt = null
	): Campaign {
		return new Campaign( $id, $name, 'Anunciante', Campaign::TYPE_HTML, $enabled, $priority, $zones, $categories, $startsAt, null, '<div>x</div>', '', '' );
	}

	private function now(): DateTimeImmutable {
		return new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
	}
}
