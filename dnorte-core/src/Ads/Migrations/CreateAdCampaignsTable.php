<?php
/**
 * @package DNorteCore\Ads\Migrations
 */

declare(strict_types=1);

namespace DNorteCore\Ads\Migrations;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Migrator\Contracts\Migration;

final class CreateAdCampaignsTable implements Migration {

	public function name(): string {
		return 'create_ad_campaigns_table';
	}

	public function up( DatabaseManager $database ): void {
		$table = $database->table( 'ad_campaigns' );

		// zones/categories son listas separadas por comas (ej. "cabecera,inicio"),
		// no una tabla de unión aparte: con cinco espacios fijos y un puñado de
		// campañas reales, una consulta con JOIN no aporta nada que no resuelva ya
		// filtrar en PHP (ver Campaign::appliesToZone()/CampaignRepository::forZone()),
		// y evita el coste de mantenimiento de una tabla más. Revisar si el número
		// de campañas activas simultáneas crece mucho.
		$database->unprepared(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(191) NOT NULL,
				advertiser VARCHAR(191) NOT NULL DEFAULT '',
				type VARCHAR(32) NOT NULL DEFAULT 'html',
				enabled TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
				priority INT NOT NULL DEFAULT 0,
				zones VARCHAR(255) NOT NULL DEFAULT '',
				categories VARCHAR(255) NOT NULL DEFAULT '',
				starts_at DATETIME NULL,
				ends_at DATETIME NULL,
				html LONGTEXT NOT NULL,
				adsense_client_id VARCHAR(64) NOT NULL DEFAULT '',
				adsense_slot_id VARCHAR(64) NOT NULL DEFAULT '',
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY priority (priority)
			)"
		);
	}

	public function down( DatabaseManager $database ): void {
		$database->unprepared( "DROP TABLE IF EXISTS {$database->table( 'ad_campaigns' )}" );
	}
}
