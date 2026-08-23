<?php
/**
 * @package DNorteCore\Workflow\Shifts
 */

declare(strict_types=1);

namespace DNorteCore\Workflow\Shifts;

use DateTimeImmutable;
use Exception;

final class Shift {

	public function __construct(
		public readonly int $id,
		public readonly int $userId,
		public readonly string $role,
		public readonly string $startsAt,
		public readonly string $endsAt,
		public readonly string $notes
	) {
	}

	/**
	 * @throws Exception Si startsAt/endsAt no son fechas MySQL válidas — no debería
	 *                   ocurrir para un Shift leído de la base de datos, donde ambas
	 *                   columnas son DATETIME NOT NULL.
	 */
	public function isActiveAt( DateTimeImmutable $moment ): bool {
		$starts = new DateTimeImmutable( $this->startsAt );
		$ends   = new DateTimeImmutable( $this->endsAt );

		return $moment >= $starts && $moment <= $ends;
	}
}
