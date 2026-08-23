<?php
/**
 * @package DNorteCore\Workflow\Shifts
 */

declare(strict_types=1);

namespace DNorteCore\Workflow\Shifts;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Migrator\Contracts\Migration;

final class CreateShiftsTable implements Migration {

	public function name(): string {
		return 'create_shifts_table';
	}

	public function up( DatabaseManager $database ): void {
		$table = $database->table( 'shifts' );

		$database->unprepared(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT UNSIGNED NOT NULL,
				role VARCHAR(64) NOT NULL,
				starts_at DATETIME NOT NULL,
				ends_at DATETIME NOT NULL,
				notes VARCHAR(255) NOT NULL DEFAULT '',
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY user_id (user_id),
				KEY starts_at (starts_at),
				KEY ends_at (ends_at)
			)"
		);
	}

	public function down( DatabaseManager $database ): void {
		$database->unprepared( "DROP TABLE IF EXISTS {$database->table( 'shifts' )}" );
	}
}
