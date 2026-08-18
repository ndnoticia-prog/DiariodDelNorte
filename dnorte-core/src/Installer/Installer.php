<?php
/**
 * Orquesta la instalación/actualización: corre las migraciones pendientes y deja
 * constancia de la versión instalada en wp_options (`dnorte_core_installed_version`).
 *
 * @package DNorteCore\Installer
 */

declare(strict_types=1);

namespace DNorteCore\Installer;

use DNorteCore\Migrator\Migrator;

final class Installer {

	private const OPTION_INSTALLED_VERSION = 'dnorte_core_installed_version';

	public function __construct( private readonly Migrator $migrator ) {
	}

	/**
	 * @param list<\DNorteCore\Migrator\Contracts\Migration> $migrations
	 */
	public function install( array $migrations, string $currentVersion ): void {
		$this->migrator->run( $migrations );

		update_option( self::OPTION_INSTALLED_VERSION, $currentVersion );
	}

	public function installedVersion(): ?string {
		$value = get_option( self::OPTION_INSTALLED_VERSION, null );

		return is_string( $value ) ? $value : null;
	}

	public function needsInstall( string $currentVersion ): bool {
		return $this->installedVersion() !== $currentVersion;
	}
}
