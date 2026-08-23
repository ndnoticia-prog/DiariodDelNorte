<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Workflow\Shifts;

use DateTimeImmutable;
use DNorteCore\Tests\Unit\TestCase;
use DNorteCore\Workflow\Shifts\Shift;

final class ShiftTest extends TestCase {

	public function test_is_active_at_is_true_within_the_range_inclusive(): void {
		$shift = new Shift( 1, 5, 'editor_en_turno', '2026-08-22 08:00:00', '2026-08-22 16:00:00', '' );

		self::assertTrue( $shift->isActiveAt( new DateTimeImmutable( '2026-08-22 08:00:00' ) ) );
		self::assertTrue( $shift->isActiveAt( new DateTimeImmutable( '2026-08-22 12:00:00' ) ) );
		self::assertTrue( $shift->isActiveAt( new DateTimeImmutable( '2026-08-22 16:00:00' ) ) );
	}

	public function test_is_active_at_is_false_outside_the_range(): void {
		$shift = new Shift( 1, 5, 'editor_en_turno', '2026-08-22 08:00:00', '2026-08-22 16:00:00', '' );

		self::assertFalse( $shift->isActiveAt( new DateTimeImmutable( '2026-08-22 07:59:59' ) ) );
		self::assertFalse( $shift->isActiveAt( new DateTimeImmutable( '2026-08-22 16:00:01' ) ) );
	}
}
