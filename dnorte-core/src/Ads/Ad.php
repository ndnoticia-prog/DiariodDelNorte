<?php
/**
 * @package DNorteCore\Ads
 */

declare(strict_types=1);

namespace DNorteCore\Ads;

use DateTimeImmutable;
use DateTimeZone;

final class Ad {

	public function __construct(
		public readonly int $id,
		public readonly string $slotKey,
		public readonly string $html,
		public readonly bool $enabled,
		public readonly ?string $startsAt,
		public readonly ?string $endsAt
	) {
	}

	/**
	 * Mismo criterio que Workflow\Shifts\Shift::isActiveAt(): recibe el momento en
	 * vez de calcular "ahora" internamente, para que el llamador controle la hora
	 * exacta en las pruebas sin depender del reloj real.
	 */
	public function isActiveAt( DateTimeImmutable $moment ): bool {
		if ( ! $this->enabled ) {
			return false;
		}

		if ( $this->startsAt !== null && $moment < new DateTimeImmutable( $this->startsAt, new DateTimeZone( 'UTC' ) ) ) {
			return false;
		}

		if ( $this->endsAt !== null && $moment > new DateTimeImmutable( $this->endsAt, new DateTimeZone( 'UTC' ) ) ) {
			return false;
		}

		return true;
	}
}
