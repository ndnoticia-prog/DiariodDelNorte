<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Workflow;

use DNorteCore\Config\Config;
use DNorteCore\Database\DatabaseManager;
use DNorteCore\Workflow\Shifts\ShiftRepository;
use DNorteCore\Workflow\Shifts\ShiftsAdminPage;
use WP_UnitTestCase;

final class ShiftsAdminPageTest extends WP_UnitTestCase {

	public function test_admin_pages_returns_the_turnos_page_with_the_editor_capability(): void {
		global $wpdb;

		$page = new ShiftsAdminPage(
			new ShiftRepository( new DatabaseManager( $wpdb ) ),
			new Config( array( 'workflow' => array( 'shift_roles' => array( 'editor_en_turno' => 'Editor en turno' ) ) ) )
		);

		$pages = $page->adminPages();

		self::assertCount( 1, $pages );
		self::assertSame( 'dnorte-turnos', $pages[0]->slug );
		self::assertSame( 'edit_others_posts', $pages[0]->capability );
		self::assertIsCallable( $pages[0]->render );
	}
}
