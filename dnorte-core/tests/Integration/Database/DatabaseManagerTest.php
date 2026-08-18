<?php
/**
 * Pruebas de integración con WordPress/MySQL reales — DatabaseManager no es
 * "cubrible de forma fiable con Brain Monkey" (depende de $wpdb real). Ver
 * docs/Architecture.md, "Por qué DatabaseManager/Migrator/Installer no tienen
 * pruebas unitarias con mocks".
 *
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Database;

use DNorteCore\Database\DatabaseManager;
use WP_UnitTestCase;
use wpdb;

final class DatabaseManagerTest extends WP_UnitTestCase {

	private DatabaseManager $database;

	/**
	 * Tabla propia de fixtures, creada/eliminada una sola vez para toda la clase
	 * (fuera de la transacción por-test de WP_UnitTestCase): CREATE/DROP TABLE
	 * produce un COMMIT implícito en MySQL, así que hacerlo dentro de un test
	 * individual rompería el aislamiento transaccional del resto de la suite.
	 */
	public static function wpSetUpBeforeClass( \WP_UnitTest_Factory $factory ): void {
		global $wpdb;
		$database = new DatabaseManager( $wpdb );
		$database->unprepared(
			"CREATE TABLE IF NOT EXISTS {$database->table( 'test_fixture' )} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(191) NOT NULL,
				PRIMARY KEY (id)
			)"
		);
	}

	public static function wpTearDownAfterClass(): void {
		global $wpdb;
		$database = new DatabaseManager( $wpdb );
		$database->unprepared( "DROP TABLE IF EXISTS {$database->table( 'test_fixture' )}" );
	}

	protected function setUp(): void {
		parent::setUp();

		global $wpdb;
		$this->database = new DatabaseManager( $wpdb );
	}

	public function test_table_adds_the_dnorte_infix_with_the_wordpress_prefix(): void {
		global $wpdb;

		self::assertSame( $wpdb->prefix . 'dnorte_test_fixture', $this->database->table( 'test_fixture' ) );
	}

	public function test_wp_table_uses_the_wordpress_prefix_without_the_dnorte_infix(): void {
		global $wpdb;

		self::assertSame( $wpdb->prefix . 'posts', $this->database->wpTable( 'posts' ) );
	}

	public function test_insert_then_select_returns_the_inserted_row(): void {
		$table = $this->database->table( 'test_fixture' );

		$id = $this->database->insert( $table, array( 'name' => 'Ejemplo' ) );

		self::assertGreaterThan( 0, $id );

		$rows = $this->database->select( "SELECT * FROM {$table} WHERE id = %d", array( $id ) );

		self::assertCount( 1, $rows );
		self::assertSame( 'Ejemplo', $rows[0]['name'] );
	}

	public function test_select_one_returns_null_when_nothing_matches(): void {
		$table = $this->database->table( 'test_fixture' );

		$row = $this->database->selectOne( "SELECT * FROM {$table} WHERE id = %d", array( 999999999 ) );

		self::assertNull( $row );
	}

	public function test_update_modifies_only_the_matching_row(): void {
		$table = $this->database->table( 'test_fixture' );

		$targetId = $this->database->insert( $table, array( 'name' => 'Original' ) );
		$otherId  = $this->database->insert( $table, array( 'name' => 'Sin tocar' ) );

		$affected = $this->database->update( $table, array( 'name' => 'Actualizado' ), array( 'id' => $targetId ) );

		self::assertSame( 1, $affected );
		self::assertSame( 'Actualizado', $this->database->selectOne( "SELECT * FROM {$table} WHERE id = %d", array( $targetId ) )['name'] );
		self::assertSame( 'Sin tocar', $this->database->selectOne( "SELECT * FROM {$table} WHERE id = %d", array( $otherId ) )['name'] );
	}

	public function test_delete_removes_only_the_matching_row(): void {
		$table = $this->database->table( 'test_fixture' );

		$id = $this->database->insert( $table, array( 'name' => 'Para borrar' ) );

		$affected = $this->database->delete( $table, array( 'id' => $id ) );

		self::assertSame( 1, $affected );
		self::assertNull( $this->database->selectOne( "SELECT * FROM {$table} WHERE id = %d", array( $id ) ) );
	}

	public function test_statement_executes_a_prepared_update_without_a_tabular_result(): void {
		$table = $this->database->table( 'test_fixture' );
		$id    = $this->database->insert( $table, array( 'name' => 'Antes' ) );

		$ok = $this->database->statement( "UPDATE {$table} SET name = %s WHERE id = %d", array( 'Después', $id ) );

		self::assertTrue( $ok );
		self::assertSame( 'Después', $this->database->selectOne( "SELECT * FROM {$table} WHERE id = %d", array( $id ) )['name'] );
	}
}
