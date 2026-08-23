<?php
/**
 * @package DNorteCore\Ads\Migrations
 */

declare(strict_types=1);

namespace DNorteCore\Ads\Migrations;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Migrator\Contracts\Migration;

final class CreateAdsTable implements Migration {

	public function name(): string {
		return 'create_ads_table';
	}

	public function up( DatabaseManager $database ): void {
		$table = $database->table( 'ads' );

		// Un único anuncio activo por espacio en v1 (UNIQUE KEY slot_key) — los
		// cinco espacios pedidos para Diario del Norte no necesitan rotación entre
		// varios anunciantes todavía; ampliar a varias filas por slot_key (quitando
		// la UNIQUE KEY) es el camino natural si eso cambia. starts_at/ends_at
		// admiten NULL a propósito (a diferencia de otras tablas de la plataforma
		// que usan '' como "sin valor"): '' no es una fecha DATETIME válida, NULL sí
		// es la representación correcta de "sin límite" para una columna de fecha.
		$database->unprepared(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				slot_key VARCHAR(32) NOT NULL,
				html LONGTEXT NOT NULL,
				enabled TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
				starts_at DATETIME NULL,
				ends_at DATETIME NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY slot_key (slot_key)
			)"
		);
	}

	public function down( DatabaseManager $database ): void {
		$database->unprepared( "DROP TABLE IF EXISTS {$database->table( 'ads' )}" );
	}
}
