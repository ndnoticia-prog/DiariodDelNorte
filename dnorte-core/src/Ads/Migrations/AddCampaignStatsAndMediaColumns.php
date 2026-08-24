<?php
/**
 * Amplía `dnorte_ad_campaigns` (creada en v0.1.0-alpha.13) con lo que pedía el
 * panel real de campañas del cliente: tipo "image" (banner con imagen propia,
 * `image_url`/`link_url`), contadores de impresiones/clics para la columna
 * "Estadísticas" (`Campaign::ctr()`), y `evidence_ids` — ids de adjuntos de la
 * Biblioteca de medios que prueban que la campaña corrió, para "Subir evidencia"/
 * "Generar informe". `ALTER TABLE` en vez de reescribir CreateAdCampaignsTable —
 * ninguna migración ya publicada se reescribe (ver el docblock de
 * Installer\MigrationRegistry).
 *
 * @package DNorteCore\Ads\Migrations
 */

declare(strict_types=1);

namespace DNorteCore\Ads\Migrations;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Migrator\Contracts\Migration;

final class AddCampaignStatsAndMediaColumns implements Migration {

	public function name(): string {
		return 'add_campaign_stats_and_media_columns';
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
			'image_url'    => "image_url VARCHAR(500) NOT NULL DEFAULT ''",
			'link_url'     => "link_url VARCHAR(500) NOT NULL DEFAULT ''",
			'impressions'  => 'impressions BIGINT UNSIGNED NOT NULL DEFAULT 0',
			'clicks'       => 'clicks BIGINT UNSIGNED NOT NULL DEFAULT 0',
			'evidence_ids' => "evidence_ids VARCHAR(500) NOT NULL DEFAULT ''",
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
