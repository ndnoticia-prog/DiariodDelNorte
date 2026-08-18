<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Hooks;

use Brain\Monkey\Functions;
use DNorteCore\Hooks\HookManager;
use DNorteCore\Tests\Unit\TestCase;
use Mockery;

final class HookManagerTest extends TestCase {

	public function test_add_action_before_flush_does_not_call_wordpress_yet(): void {
		Functions\expect( 'add_action' )->never();

		$manager = new HookManager();
		$manager->addAction(
			'init',
			static function (): void {
			}
		);

		$this->addToAssertionCount( 1 );
	}

	public function test_flush_wires_every_pending_action_and_filter(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'init', Mockery::type( 'callable' ), 10, 1 );
		Functions\expect( 'add_filter' )
			->once()
			->with( 'the_content', Mockery::type( 'callable' ), 20, 2 );

		$manager = new HookManager();
		$manager->addAction(
			'init',
			static function (): void {
			}
		);
		$manager->addFilter(
			'the_content',
			static function ( string $content ): string {
				return $content;
			},
			20,
			2
		);

		$manager->flush();

		$this->addToAssertionCount( 1 );
	}

	public function test_after_flush_new_registrations_wire_immediately(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'wp_loaded', Mockery::type( 'callable' ), 10, 1 );

		$manager = new HookManager();
		$manager->flush();

		$manager->addAction(
			'wp_loaded',
			static function (): void {
			}
		);

		$this->addToAssertionCount( 1 );
	}

	public function test_remove_unregisters_a_previously_wired_action(): void {
		Functions\expect( 'add_action' )->once();
		Functions\expect( 'remove_action' )
			->once()
			->with( 'init', Mockery::type( 'callable' ), 10 );

		$manager = new HookManager();
		$token   = $manager->addAction(
			'init',
			static function (): void {
			}
		);
		$manager->flush();

		$manager->remove( $token );

		$this->addToAssertionCount( 1 );
	}

	public function test_remove_with_an_unknown_token_is_a_no_op(): void {
		Functions\expect( 'remove_action' )->never();
		Functions\expect( 'remove_filter' )->never();

		$manager = new HookManager();

		$manager->remove( 'never-registered-token' );

		$this->addToAssertionCount( 1 );
	}
}
