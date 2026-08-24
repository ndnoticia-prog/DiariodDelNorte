<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Ads;

use DNorteCore\Ads\CampaignHistoryRepository;
use DNorteCore\Database\DatabaseManager;
use WP_UnitTestCase;

final class CampaignHistoryRepositoryTest extends WP_UnitTestCase {

	public function test_record_and_recent_round_trip(): void {
		global $wpdb;
		$repository = new CampaignHistoryRepository( new DatabaseManager( $wpdb ) );

		$repository->record( 42, 'Campaña de prueba', 'creada', 'Ana Editora' );
		$repository->record( 42, 'Campaña de prueba', 'activada', 'Ana Editora', 'Nota opcional' );

		$entries = $repository->recent( 10 );

		self::assertGreaterThanOrEqual( 2, count( $entries ) );
		self::assertSame( 'activada', $entries[0]->action, 'recent() debe devolver lo más nuevo primero.' );
		self::assertSame( 42, $entries[0]->campaignId );
		self::assertSame( 'Campaña de prueba', $entries[0]->campaignName );
		self::assertSame( 'Ana Editora', $entries[0]->actor );
		self::assertSame( 'Nota opcional', $entries[0]->details );
	}

	public function test_recent_keeps_entries_of_a_campaign_already_deleted(): void {
		global $wpdb;
		$repository = new CampaignHistoryRepository( new DatabaseManager( $wpdb ) );

		// campaign_id ya no existe en dnorte_ad_campaigns — el historial no depende
		// de un JOIN a esa fila, ver el docblock de la migración.
		$repository->record( 999999, 'Campaña ya borrada', 'borrada', 'Ana Editora' );

		$entries = $repository->recent( 10 );
		$names   = array_map( static fn ( $entry ): string => $entry->campaignName, $entries );

		self::assertContains( 'Campaña ya borrada', $names );
	}
}
