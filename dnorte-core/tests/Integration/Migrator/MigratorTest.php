<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Migrator;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Migrator\Contracts\Migration;
use DNorteCore\Migrator\Migrator;
use WP_UnitTestCase;

final class MigratorTest extends WP_UnitTestCase {

	private const FIXTURE_NAME = 'integration_test_only_fixture_migration';

	private DatabaseManager $database;

	private Migrator $migrator;

	protected function setUp(): void {
		parent::setUp();

		global $wpdb;
		$this->database = new DatabaseManager( $wpdb );
		$this->migrator = new Migrator( $this->database );
	}

	protected function tearDown(): void {
		// Migrator::run() escribe en la tabla COMPARTIDA `dnorte_migrations` — la
		// misma que Installer/CoreServiceProvider::maybeRunUpgrade() ya gestionan en
		// cada arranque real (ver docs/Architecture.md). Nunca recrear ni vaciar esa
		// tabla completa en un test: solo retirar la fila propia de esta prueba.
		$this->database->delete( $this->database->table( 'migrations' ), array( 'name' => self::FIXTURE_NAME ) );

		parent::tearDown();
	}

	public function test_run_applies_a_pending_migration_and_records_it(): void {
		$migration = $this->fixtureMigration();

		$justApplied = $this->migrator->run( array( $migration ) );

		self::assertSame( array( self::FIXTURE_NAME ), $justApplied );
		self::assertTrue( $migration->wasApplied() );
		self::assertContains( self::FIXTURE_NAME, $this->migrator->applied() );
	}

	public function test_run_does_not_reapply_a_migration_already_recorded(): void {
		$this->migrator->run( array( $this->fixtureMigration() ) );

		$secondMigration = $this->fixtureMigration();
		$secondRun       = $this->migrator->run( array( $secondMigration ) );

		self::assertSame( array(), $secondRun );
		self::assertFalse( $secondMigration->wasApplied() );
	}

	private function fixtureMigration(): Migration {
		return new class( self::FIXTURE_NAME ) implements Migration {

			private bool $applied = false;

			public function __construct( private readonly string $migrationName ) {
			}

			public function name(): string {
				return $this->migrationName;
			}

			public function up( DatabaseManager $database ): void {
				$this->applied = true;
			}

			public function down( DatabaseManager $database ): void {
				$this->applied = false;
			}

			public function wasApplied(): bool {
				return $this->applied;
			}
		};
	}
}
