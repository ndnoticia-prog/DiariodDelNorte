<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Installer;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Installer\MigrationRegistry;
use DNorteCore\Migrator\Migrator;
use WP_UnitTestCase;

final class MigrationRegistryTest extends WP_UnitTestCase {

	public function test_all_migrations_have_unique_names(): void {
		$names = array_map(
			static fn ( $migration ): string => $migration->name(),
			MigrationRegistry::all()
		);

		self::assertSame( $names, array_unique( $names ) );
	}

	public function test_all_migrations_apply_cleanly_and_are_idempotent(): void {
		global $wpdb;

		$database = new DatabaseManager( $wpdb );
		$migrator = new Migrator( $database );

		// No asume nada sobre si el bootstrap real ya las corrió (depende de si el
		// arnés de wordpress-develop reinstala la base de datos en cada invocación de
		// `composer test:integration`, algo que resultó ser menos predecible de lo
		// documentado — ver "Fixed" en CHANGELOG.md). En su lugar, el propio test
		// controla las dos corridas y verifica la idempotencia directamente.
		$firstRun  = $migrator->run( MigrationRegistry::all() );
		$secondRun = $migrator->run( MigrationRegistry::all() );

		self::assertSame(
			array(
				'create_editorial_notes_table',
				'create_shifts_table',
				'add_search_fulltext_index_to_posts',
				'create_pageviews_table',
				'create_ads_table',
				'drop_legacy_ads_table',
				'create_ad_campaigns_table',
				'add_campaign_stats_and_media_columns',
				'create_ad_campaign_history_table',
			),
			$firstRun
		);
		self::assertSame( array(), $secondRun );
	}
}
