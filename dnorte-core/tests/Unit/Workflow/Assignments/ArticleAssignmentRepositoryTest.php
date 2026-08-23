<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Workflow\Assignments;

use Brain\Monkey\Functions;
use DNorteCore\Tests\Unit\TestCase;
use DNorteCore\Workflow\Assignments\ArticleAssignmentRepository;

final class ArticleAssignmentRepositoryTest extends TestCase {

	public function test_assign_updates_the_meta_key_with_the_user_id(): void {
		Functions\expect( 'update_post_meta' )
			->once()
			->with( 42, '_dnorte_assigned_to', 7 );

		( new ArticleAssignmentRepository() )->assign( 42, 7 );

		$this->addToAssertionCount( 1 );
	}

	public function test_unassign_deletes_the_meta_key(): void {
		Functions\expect( 'delete_post_meta' )
			->once()
			->with( 42, '_dnorte_assigned_to' );

		( new ArticleAssignmentRepository() )->unassign( 42 );

		$this->addToAssertionCount( 1 );
	}

	public function test_assigned_to_returns_null_when_there_is_no_assignment(): void {
		Functions\expect( 'get_post_meta' )->once()->andReturn( '' );

		self::assertNull( ( new ArticleAssignmentRepository() )->assignedTo( 42 ) );
	}

	public function test_assigned_to_returns_the_user_id_when_assigned(): void {
		Functions\expect( 'get_post_meta' )->once()->andReturn( '7' );

		self::assertSame( 7, ( new ArticleAssignmentRepository() )->assignedTo( 42 ) );
	}
}
