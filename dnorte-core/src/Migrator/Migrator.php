<?php
/**
 * Versiona el esquema propio de la plataforma en una tabla `{prefix}dnorte_migrations`.
 * Cada migración es idempotente y se identifica por su name(); una vez aplicada no
 * vuelve a ejecutarse.
 *
 * @package DNorteCore\Migrator
 */

declare(strict_types=1);

namespace DNorteCore\Migrator;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Migrator\Contracts\Migration;

final class Migrator {

	public function __construct( private readonly DatabaseManager $database ) {
	}

	public function ensureMigrationsTableExists(): void {
		$table = $this->database->table( 'migrations' );

		$this->database->unprepared(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(191) NOT NULL,
				applied_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY name (name)
			)"
		);
	}

	/**
	 * @return list<string> Nombres de las migraciones ya aplicadas.
	 */
	public function applied(): array {
		$table = $this->database->table( 'migrations' );
		$rows  = $this->database->select( "SELECT name FROM {$table} ORDER BY id ASC" );

		return array_map( static fn ( array $row ): string => (string) $row['name'], $rows );
	}

	/**
	 * Ejecuta up() de cada migración pendiente (en el orden dado) y la registra.
	 *
	 * @param list<Migration> $migrations
	 * @return list<string> Nombres de las migraciones recién aplicadas en esta corrida.
	 */
	public function run( array $migrations ): array {
		$this->ensureMigrationsTableExists();

		$alreadyApplied = $this->applied();
		$justApplied    = array();

		foreach ( $migrations as $migration ) {
			if ( in_array( $migration->name(), $alreadyApplied, true ) ) {
				continue;
			}

			$migration->up( $this->database );

			$this->database->insert(
				$this->database->table( 'migrations' ),
				array(
					'name'       => $migration->name(),
					'applied_at' => gmdate( 'Y-m-d H:i:s' ),
				)
			);

			$justApplied[] = $migration->name();
		}

		return $justApplied;
	}
}
