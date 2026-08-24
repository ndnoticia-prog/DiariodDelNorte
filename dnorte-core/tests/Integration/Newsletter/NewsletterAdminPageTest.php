<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Newsletter;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Newsletter\NewsletterAdminPage;
use DNorteCore\Newsletter\Subscribers\NewsletterSubscriberRepository;
use WP_UnitTestCase;

final class NewsletterAdminPageTest extends WP_UnitTestCase {

	public function test_admin_pages_returns_its_own_top_level_entry(): void {
		global $wpdb;

		$page = new NewsletterAdminPage( new NewsletterSubscriberRepository( new DatabaseManager( $wpdb ) ) );

		$pages = $page->adminPages();

		self::assertCount( 1, $pages );
		self::assertSame( 'dnorte-newsletter', $pages[0]->slug );
		self::assertSame( 'edit_others_posts', $pages[0]->capability );
		self::assertNull( $pages[0]->parentSlug );
		self::assertIsCallable( $pages[0]->render );
	}
}
