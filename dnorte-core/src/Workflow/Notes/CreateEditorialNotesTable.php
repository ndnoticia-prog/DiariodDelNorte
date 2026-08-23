<?php
/**
 * Tabla propia para comentarios internos de redacción — deliberadamente separada de
 * la tabla nativa de comentarios de WordPress (pensada para comentarios públicos de
 * lectores, con su propio flujo de moderación que no aplica aquí). Mismo criterio que
 * ND Platform.
 *
 * @package DNorteCore\Workflow\Notes
 */

declare(strict_types=1);

namespace DNorteCore\Workflow\Notes;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Migrator\Contracts\Migration;

final class CreateEditorialNotesTable implements Migration {

	public function name(): string {
		return 'create_editorial_notes_table';
	}

	public function up( DatabaseManager $database ): void {
		$table = $database->table( 'editorial_notes' );

		$database->unprepared(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				post_id BIGINT UNSIGNED NOT NULL,
				author_id BIGINT UNSIGNED NOT NULL,
				type VARCHAR(32) NOT NULL DEFAULT 'general',
				body TEXT NOT NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY post_id (post_id)
			)"
		);
	}

	public function down( DatabaseManager $database ): void {
		$database->unprepared( "DROP TABLE IF EXISTS {$database->table( 'editorial_notes' )}" );
	}
}
