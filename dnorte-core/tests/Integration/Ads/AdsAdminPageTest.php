<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Ads;

use DNorteCore\Ads\AdRepository;
use DNorteCore\Ads\AdsAdminPage;
use DNorteCore\Config\Config;
use DNorteCore\Database\DatabaseManager;
use WP_UnitTestCase;

final class AdsAdminPageTest extends WP_UnitTestCase {

	public function test_admin_pages_returns_its_own_top_level_entry_requiring_manage_options(): void {
		global $wpdb;

		$page = new AdsAdminPage(
			new AdRepository( new DatabaseManager( $wpdb ) ),
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
}
