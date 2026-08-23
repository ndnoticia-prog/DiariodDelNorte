<?php
/**
 * Primera migración de la plataforma que altera una tabla NATIVA de WordPress
 * (`wp_posts`), no una tabla propia — se documenta aquí porque es una excepción
 * deliberada al criterio general ("las migraciones crean tablas `dnorte_*`, nunca
 * tocan el esquema de WordPress core"): un índice FULLTEXT sobre post_title/
 * post_content es la única forma de que MySQL/MariaDB pueda calcular relevancia
 * (MATCH ... AGAINST) en vez del LIKE '%término%' que usa WordPress por defecto,
 * y no existe ningún filtro de WordPress que permita añadir un índice sin ALTER TABLE.
 *
 * @package DNorteCore\Search\Fulltext
 */

declare(strict_types=1);

namespace DNorteCore\Search\Fulltext;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Migrator\Contracts\Migration;

final class CreateSearchFulltextIndex implements Migration {

	private const INDEX_NAME = 'dnorte_search_fulltext';

	public function name(): string {
		return 'add_search_fulltext_index_to_posts';
	}

	public function up( DatabaseManager $database ): void {
		$postsTable = $database->wpTable( 'posts' );

		// ALTER TABLE ... ADD FULLTEXT no admite "IF NOT EXISTS" en MySQL/MariaDB
		// (a diferencia del CREATE TABLE de las migraciones propias) — se comprueba
		// a mano para que la migración siga siendo idempotente si alguna vez se
		// reejecuta fuera del seguimiento normal de Migrator (ej. un entorno
		// restaurado desde un backup que ya tenía el índice).
		$existing = $database->select(
			"SHOW INDEX FROM {$postsTable} WHERE Key_name = '" . self::INDEX_NAME . "'"
		);

		if ( $existing !== array() ) {
			return;
		}

		// En un wp_posts muy grande esto puede tardar (bloquea la tabla mientras
		// MySQL construye el índice) — aceptable para el volumen actual del sitio;
		// revisar si el archivo editorial crece a decenas de miles de artículos.
		$database->unprepared(
			"ALTER TABLE {$postsTable} ADD FULLTEXT " . self::INDEX_NAME . ' (post_title, post_content)'
		);
	}

	public function down( DatabaseManager $database ): void {
		$postsTable = $database->wpTable( 'posts' );

		$database->unprepared( "ALTER TABLE {$postsTable} DROP INDEX " . self::INDEX_NAME );
	}
}
