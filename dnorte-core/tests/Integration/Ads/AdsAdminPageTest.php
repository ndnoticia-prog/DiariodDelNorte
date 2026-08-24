<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Ads;

use DNorteCore\Ads\AdsAdminPage;
use DNorteCore\Ads\Campaign;
use DNorteCore\Ads\CampaignHistoryRepository;
use DNorteCore\Ads\CampaignRepository;
use DNorteCore\Config\Config;
use DNorteCore\Database\DatabaseManager;
use WP_UnitTestCase;

final class AdsAdminPageTest extends WP_UnitTestCase {

	public function test_admin_pages_returns_its_own_top_level_entry_requiring_manage_options(): void {
		global $wpdb;

		$page = new AdsAdminPage(
			new CampaignRepository( new DatabaseManager( $wpdb ) ),
			new CampaignHistoryRepository( new DatabaseManager( $wpdb ) ),
			new Config( array( 'ads' => array( 'slots' => array( 'cabecera' => 'Cabecera' ) ) ) )
		);

		$pages = $page->adminPages();

		self::assertCount( 1, $pages );
		self::assertSame( 'dnorte-publicidad', $pages[0]->slug );
		self::assertSame( 'manage_options', $pages[0]->capability );
		// Regresión del hallazgo de v0.1.0-alpha.11: su propia entrada de nivel
		// superior, nunca anidada bajo otro módulo.
		self::assertNull( $pages[0]->parentSlug );
		self::assertIsCallable( $pages[0]->render );
	}

	/**
	 * Providers\AdsServiceProvider::maybeDownloadReportPdf() usa este helper
	 * público para armar el PDF sin duplicar el mapa TYPES.
	 */
	public function test_type_label_resolves_a_known_type_and_falls_back_to_the_raw_value(): void {
		self::assertSame( 'Google Ad Manager', AdsAdminPage::typeLabel( Campaign::TYPE_GAM ) );
		self::assertSame( 'inexistente', AdsAdminPage::typeLabel( 'inexistente' ) );
	}
}
