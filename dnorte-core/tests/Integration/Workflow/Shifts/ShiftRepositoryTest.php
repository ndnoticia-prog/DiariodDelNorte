<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Workflow\Shifts;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Workflow\Shifts\ShiftRepository;
use WP_UnitTestCase;

final class ShiftRepositoryTest extends WP_UnitTestCase {

	private ShiftRepository $shifts;

	protected function setUp(): void {
		parent::setUp();

		global $wpdb;
		$this->shifts = new ShiftRepository( new DatabaseManager( $wpdb ) );
	}

	public function test_on_duty_now_includes_a_shift_covering_the_current_moment(): void {
		$userId = self::factory()->user->create();

		$this->shifts->create(
			$userId,
			'editor_en_turno',
			gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
			gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS )
		);

		$onDuty = $this->shifts->onDutyNow();

		self::assertCount( 1, $onDuty );
		self::assertSame( $userId, $onDuty[0]->userId );
		self::assertSame( 'editor_en_turno', $onDuty[0]->role );
	}

	public function test_on_duty_now_excludes_a_shift_that_has_not_started_yet(): void {
		$userId = self::factory()->user->create();

		$this->shifts->create(
			$userId,
			'editor_en_turno',
			gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ),
			gmdate( 'Y-m-d H:i:s', time() + 2 * HOUR_IN_SECONDS )
		);

		self::assertSame( array(), $this->shifts->onDutyNow() );
	}

	public function test_on_duty_now_excludes_a_shift_that_already_ended(): void {
		$userId = self::factory()->user->create();

		$this->shifts->create(
			$userId,
			'editor_en_turno',
			gmdate( 'Y-m-d H:i:s', time() - 2 * HOUR_IN_SECONDS ),
			gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS )
		);

		self::assertSame( array(), $this->shifts->onDutyNow() );
	}

	public function test_upcoming_orders_by_start_time_ascending(): void {
		$userId = self::factory()->user->create();

		$this->shifts->create(
			$userId,
			'redactor_en_turno',
			gmdate( 'Y-m-d H:i:s', time() + 4 * HOUR_IN_SECONDS ),
			gmdate( 'Y-m-d H:i:s', time() + 5 * HOUR_IN_SECONDS ),
			'Segundo'
		);
		$this->shifts->create(
			$userId,
			'redactor_en_turno',
			gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ),
			gmdate( 'Y-m-d H:i:s', time() + 2 * HOUR_IN_SECONDS ),
			'Primero'
		);

		$upcoming = $this->shifts->upcoming();

		self::assertCount( 2, $upcoming );
		self::assertSame( 'Primero', $upcoming[0]->notes );
		self::assertSame( 'Segundo', $upcoming[1]->notes );
	}

	public function test_delete_removes_the_shift(): void {
		$userId = self::factory()->user->create();

		$id = $this->shifts->create(
			$userId,
			'editor_en_turno',
			gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
			gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS )
		);

		$this->shifts->delete( $id );

		self::assertSame( array(), $this->shifts->onDutyNow() );
	}
}
