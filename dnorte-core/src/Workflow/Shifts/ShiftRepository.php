<?php
/**
 * @package DNorteCore\Workflow\Shifts
 */

declare(strict_types=1);

namespace DNorteCore\Workflow\Shifts;

use DNorteCore\Database\DatabaseManager;

final class ShiftRepository {

	public function __construct( private readonly DatabaseManager $database ) {
	}

	public function create( int $userId, string $role, string $startsAt, string $endsAt, string $notes = '' ): int {
		return $this->database->insert(
			$this->database->table( 'shifts' ),
			array(
				'user_id'    => $userId,
				'role'       => $role,
				'starts_at'  => $startsAt,
				'ends_at'    => $endsAt,
				'notes'      => $notes,
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
			)
		);
	}

	public function delete( int $id ): void {
		$this->database->delete( $this->database->table( 'shifts' ), array( 'id' => $id ) );
	}

	/**
	 * Turnos activos en este momento — quién está de turno ahora mismo.
	 *
	 * @return list<Shift>
	 */
	public function onDutyNow(): array {
		$table = $this->database->table( 'shifts' );
		$now   = gmdate( 'Y-m-d H:i:s' );

		$rows = $this->database->select(
			"SELECT * FROM {$table} WHERE starts_at <= %s AND ends_at >= %s ORDER BY starts_at ASC",
			array( $now, $now )
		);

		return $this->hydrate( $rows );
	}

	/**
	 * Próximos turnos (incluye los que ya empezaron pero no han terminado).
	 *
	 * @return list<Shift>
	 */
	public function upcoming( int $limit = 50 ): array {
		$table = $this->database->table( 'shifts' );
		$now   = gmdate( 'Y-m-d H:i:s' );

		$rows = $this->database->select(
			"SELECT * FROM {$table} WHERE ends_at >= %s ORDER BY starts_at ASC LIMIT %d",
			array( $now, $limit )
		);

		return $this->hydrate( $rows );
	}

	/**
	 * @param list<array<string, mixed>> $rows
	 * @return list<Shift>
	 */
	private function hydrate( array $rows ): array {
		return array_map(
			static fn ( array $row ): Shift => new Shift(
				(int) $row['id'],
				(int) $row['user_id'],
				(string) $row['role'],
				(string) $row['starts_at'],
				(string) $row['ends_at'],
				(string) $row['notes']
			),
			$rows
		);
	}
}
