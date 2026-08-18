<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Installer;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Installer\Installer;
use DNorteCore\Migrator\Migrator;
use WP_UnitTestCase;

final class InstallerTest extends WP_UnitTestCase {

	private Installer $installer;

	protected function setUp(): void {
		parent::setUp();

		global $wpdb;
		$database        = new DatabaseManager( $wpdb );
		$this->installer = new Installer( new Migrator( $database ) );
	}

	public function test_install_runs_migrations_and_records_the_installed_version(): void {
		$this->installer->install( array(), '9.9.9-integration-test' );

		self::assertSame( '9.9.9-integration-test', $this->installer->installedVersion() );
	}

	public function test_needs_install_reflects_whether_the_stored_version_matches(): void {
		$this->installer->install( array(), '9.9.9-integration-test' );

		self::assertFalse( $this->installer->needsInstall( '9.9.9-integration-test' ) );
		self::assertTrue( $this->installer->needsInstall( '9.9.8-integration-test' ) );
	}

	public function test_the_real_dnorte_core_installation_already_ran_during_bootstrap(): void {
		// CoreServiceProvider::maybeRunUpgrade() corre en `init` en cada arranque real
		// — para cuando llega este test, dnorte-core (cargado como mu-plugin en el
		// bootstrap de integración) ya debería haberse instalado solo con la versión
		// real del plugin, exactamente igual que en un sitio real. Ver
		// docs/Architecture.md.
		self::assertSame( DNORTE_CORE_VERSION, $this->installer->installedVersion() );
	}
}
