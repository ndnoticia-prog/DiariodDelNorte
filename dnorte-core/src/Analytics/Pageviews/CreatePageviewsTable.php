<?php
/**
 * @package DNorteCore\Analytics\Pageviews
 */

declare(strict_types=1);

namespace DNorteCore\Analytics\Pageviews;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Migrator\Contracts\Migration;

final class CreatePageviewsTable implements Migration {

	public function name(): string {
		return 'create_pageviews_table';
	}

	public function up( DatabaseManager $database ): void {
		$table = $database->table( 'pageviews' );

		// Deliberadamente sin IP ni user-agent ni ningún identificador de visitante:
		// analítica propia pensada para "qué se lee" (vistas por artículo en el
		// tiempo), no para "quién lo lee" — ver PageviewController/
		// PageviewBeaconRenderer. referrer_host guarda solo el dominio (nunca la URL
		// completa con parámetros de seguimiento de terceros).
		$database->unprepared(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				post_id BIGINT UNSIGNED NOT NULL,
				referrer_host VARCHAR(255) NOT NULL DEFAULT '',
				viewed_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY post_id (post_id),
				KEY viewed_at (viewed_at)
			)"
		);
	}

	public function down( DatabaseManager $database ): void {
		$database->unprepared( "DROP TABLE IF EXISTS {$database->table( 'pageviews' )}" );
	}
}
