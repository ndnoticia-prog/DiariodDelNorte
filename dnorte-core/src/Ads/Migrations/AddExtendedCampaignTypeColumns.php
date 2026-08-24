<?php
/**
 * Amplía `dnorte_ad_campaigns` con lo que necesitan los tres tipos de campaña
 * nuevos — pedidos explícitamente en la lista real de tipos del cliente
 * (adsense/gam/html/image/video/sponsored): `video_url` (tipo "video"),
 * `description` (tipo "sponsored"), `gam_ad_unit_path`/`gam_sizes` (tipo "gam",
 * Google Ad Manager). `ALTER TABLE` sobre la tabla ya creada en
 * `v0.1.0-alpha.13`/ampliada en `v0.1.0-alpha.14` — ninguna migración ya
 * publicada se reescribe (ver el docblock de Installer\MigrationRegistry).
 *
 * @package DNorteCore\Ads\Migrations
 */

declare(strict_types=1);

namespace DNorteCore\Ads\Migrations;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Migrator\Contracts\Migration;

final class AddExtendedCampaignTypeColumns implements Migration {

	public function name(): string {
		return 'add_extended_campaign_type_columns';
	}

	public function up( DatabaseManager $database ): void {
		$table = $database->table( 'ad_campaigns' );

		foreach ( $this->columns() as $column => $definition ) {
			if ( $this->columnExists( $database, $table, $column ) ) {
				continue;
			}

			$database->unprepared( "ALTER TABLE {$table} ADD COLUMN {$definition}" );
		}
	}

	public function down( DatabaseManager $database ): void {
		$table = $database->table( 'ad_campaigns' );

		foreach ( array_keys( $this->columns() ) as $column ) {
			$database->unprepared( "ALTER TABLE {$table} DROP COLUMN {$column}" );
		}
	}

	/**
	 * @return array<string, string>
	 */
	private function columns(): array {
		return array(
			'video_url'        => "video_url VARCHAR(500) NOT NULL DEFAULT ''",
			'description'      => "description VARCHAR(255) NOT NULL DEFAULT ''",
			'gam_ad_unit_path' => "gam_ad_unit_path VARCHAR(255) NOT NULL DEFAULT ''",
			'gam_sizes'        => "gam_sizes VARCHAR(255) NOT NULL DEFAULT ''",
		);
	}

	/**
	 * `ALTER TABLE ... ADD COLUMN` no admite "IF NOT EXISTS" en MariaDB/MySQL
	 * (versiones anteriores a MariaDB 10.0.4/MySQL 8.0.29) — se comprueba a mano,
	 * mismo criterio que Search\Fulltext\CreateSearchFulltextIndex.
	 */
	private function columnExists( DatabaseManager $database, string $table, string $column ): bool {
		$existing = $database->select( "SHOW COLUMNS FROM {$table} LIKE '{$column}'" );

		return $existing !== array();
	}
}
