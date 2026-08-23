<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Workflow\Status;

use Brain\Monkey\Functions;
use DNorteCore\Tests\Unit\TestCase;
use DNorteCore\Workflow\Status\EditorialStatusRegistrar;
use Mockery;

final class EditorialStatusRegistrarTest extends TestCase {

	public function test_register_adds_both_editorial_statuses_as_internal_not_public(): void {
		Functions\stubTranslationFunctions();

		$registered = array();

		Functions\expect( 'register_post_status' )
			->twice()
			->andReturnUsing(
				function ( string $status, array $args ) use ( &$registered ): void {
					$registered[ $status ] = $args;
				}
			);

		( new EditorialStatusRegistrar() )->register();

		self::assertArrayHasKey( EditorialStatusRegistrar::IN_REVIEW, $registered );
		self::assertArrayHasKey( EditorialStatusRegistrar::NEEDS_CHANGES, $registered );

		foreach ( $registered as $args ) {
			self::assertFalse( $args['public'] );
			self::assertTrue( $args['internal'] );
			self::assertTrue( $args['show_in_admin_status_list'] );
		}
	}
}
