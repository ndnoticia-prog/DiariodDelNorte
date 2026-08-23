<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Analytics;

use DNorteCore\Analytics\AnalyticsAdminPage;
use DNorteCore\Analytics\Pageviews\PageviewRepository;
use DNorteCore\Config\Config;
use DNorteCore\Database\DatabaseManager;
use WP_UnitTestCase;

final class AnalyticsAdminPageTest extends WP_UnitTestCase {

	public function test_admin_pages_returns_its_own_top_level_entry(): void {
		global $wpdb;

		$page = new AnalyticsAdminPage(
			new PageviewRepository( new DatabaseManager( $wpdb ) ),
			new Config( array( 'analytics' => array( 'top_articles_window_days' => 7 ) ) )
		);

		$pages = $page->adminPages();

		self::assertCount( 1, $pages );
		self::assertSame( 'dnorte-analitica', $pages[0]->slug );
		self::assertSame( 'edit_others_posts', $pages[0]->capability );
		// Regresión del hallazgo de v0.1.0-alpha.11: debe ser su propia entrada de
		// nivel superior, nunca anidarse bajo "Turnos" ni ningún otro módulo.
		self::assertNull( $pages[0]->parentSlug );
		self::assertIsCallable( $pages[0]->render );
	}
}
