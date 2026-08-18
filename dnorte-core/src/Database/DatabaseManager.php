<?php
/**
 * Único punto de acceso a $wpdb de toda la plataforma. Ningún otro módulo debe
 * tocar $wpdb directamente (ver docs/Architecture.md, principio 1).
 *
 * @package DNorteCore\Database
 */

declare(strict_types=1);

namespace DNorteCore\Database;

use wpdb;

final class DatabaseManager {

	public function __construct( private readonly wpdb $wpdb ) {
	}

	/**
	 * Nombre completo de una tabla PROPIA de la plataforma (con prefijo de WordPress
	 * y el infijo `dnorte_`). Para tablas nativas de WordPress usar wpTable().
	 */
	public function table( string $name ): string {
		return $this->wpdb->prefix . 'dnorte_' . $name;
	}

	/**
	 * Nombre completo de una tabla NATIVA de WordPress (wp_posts, wp_terms, ...),
	 * para JOINs — sin el infijo `dnorte_`.
	 */
	public function wpTable( string $name ): string {
		return $this->wpdb->prefix . $name;
	}

	/**
	 * SELECT preparado que devuelve todas las filas como arrays asociativos.
	 *
	 * @param list<int|float|string> $bindings
	 * @return list<array<string, mixed>>
	 */
	public function select( string $query, array $bindings = array() ): array {
		$sql = $this->prepare( $query, $bindings );

		if ( $sql === null ) {
			return array();
		}

		/** @var list<array<string, mixed>>|null $results */
		$results = $this->wpdb->get_results( $sql, ARRAY_A );

		return $results ?? array();
	}

	/**
	 * SELECT preparado que devuelve una sola fila (o null si no hay resultados).
	 *
	 * @param list<int|float|string> $bindings
	 * @return array<string, mixed>|null
	 */
	public function selectOne( string $query, array $bindings = array() ): ?array {
		$sql = $this->prepare( $query, $bindings );

		if ( $sql === null ) {
			return null;
		}

		/** @var array<string, mixed>|null $row */
		$row = $this->wpdb->get_row( $sql, ARRAY_A );

		return $row;
	}

	/**
	 * @param array<string, mixed> $data
	 * @return int Insert ID.
	 */
	public function insert( string $table, array $data ): int {
		$this->wpdb->insert( $table, $data );

		return (int) $this->wpdb->insert_id;
	}

	/**
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $where
	 */
	public function update( string $table, array $data, array $where ): int {
		$affected = $this->wpdb->update( $table, $data, $where );

		return $affected === false ? 0 : $affected;
	}

	/**
	 * @param array<string, mixed> $where
	 */
	public function delete( string $table, array $where ): int {
		$affected = $this->wpdb->delete( $table, $where );

		return $affected === false ? 0 : $affected;
	}

	/**
	 * Ejecuta una sentencia preparada sin resultado tabular (UPDATE/DELETE a medida).
	 *
	 * @param list<int|float|string> $bindings
	 */
	public function statement( string $sql, array $bindings = array() ): bool {
		$prepared = $this->prepare( $sql, $bindings );

		return $prepared !== null && $this->wpdb->query( $prepared ) !== false;
	}

	/**
	 * Ejecuta SQL sin preparar — exclusivamente para DDL (CREATE/DROP/ALTER TABLE)
	 * dentro de una migración, que nunca admite placeholders. No usar para datos
	 * de usuario; ver Migrator para el único consumidor esperado de este método.
	 */
	public function unprepared( string $sql ): bool {
		return $this->wpdb->query( $sql ) !== false;
	}

	/**
	 * wpdb::prepare() solo puede devolver null cuando detecta un error de
	 * programación (marcadores de posición que no coinciden con los parámetros
	 * dados): en ese caso la consulta está rota y no debe ejecutarse ni con ni sin
	 * preparar — de ahí que select()/selectOne()/statement() traten null como
	 * "sin resultado" en vez de propagar un tipo inválido a $wpdb->query()/get_row().
	 *
	 * La seguridad de este método la da usar siempre $bindings cuando hay datos
	 * variables, no que $query sea un literal conocido en tiempo de compilación
	 * (que es lo que exige phpstan-wordpress con `literal-string`); todo llamador
	 * de este método pasa siempre una cadena fija con marcadores `%s`/`%d`.
	 *
	 * @param list<int|float|string> $bindings
	 */
	private function prepare( string $query, array $bindings ): ?string {
		if ( $bindings === array() ) {
			return $query;
		}

		// @phpstan-ignore argument.type
		$prepared = $this->wpdb->prepare( $query, $bindings );

		return $prepared === null ? null : $prepared;
	}
}
