<?php
/**
 * @package DNorteCore\Ads\Migrations
 */

declare(strict_types=1);

namespace DNorteCore\Ads\Migrations;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Migrator\Contracts\Migration;

final class CreateAdCampaignHistoryTable implements Migration {

	public function name(): string {
		return 'create_ad_campaign_history_table';
	}

	public function up( DatabaseManager $database ): void {
		$table = $database->table( 'ad_campaign_history' );

		// campaign_name se guarda como copia (no solo campaign_id) a propósito: el
		// historial de una campaña ya borrada (acción "delete") debe poder seguir
		// mostrando de qué campaña se trataba, sin depender de un JOIN a una fila
		// que para entonces ya no existe.
		$database->unprepared(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				campaign_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				campaign_name VARCHAR(191) NOT NULL,
				action VARCHAR(32) NOT NULL,
				actor VARCHAR(191) NOT NULL DEFAULT '',
				details VARCHAR(500) NOT NULL DEFAULT '',
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY campaign_id (campaign_id),
				KEY created_at (created_at)
			)"
		);
	}

	public function down( DatabaseManager $database ): void {
		$database->unprepared( "DROP TABLE IF EXISTS {$database->table( 'ad_campaign_history' )}" );
	}
}
