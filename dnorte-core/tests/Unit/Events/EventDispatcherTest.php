<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Events;

use DNorteCore\Events\Event;
use DNorteCore\Events\EventDispatcher;
use DNorteCore\Tests\Unit\TestCase;

final class EventDispatcherTest extends TestCase {

	public function test_dispatch_without_listeners_does_nothing(): void {
		$dispatcher = new EventDispatcher();

		// No debe lanzar ni producir efecto alguno.
		$dispatcher->dispatch( new SampleEvent( 'payload' ) );

		$this->addToAssertionCount( 1 );
	}

	public function test_dispatch_calls_every_listener_registered_for_that_event(): void {
		$dispatcher = new EventDispatcher();
		$received   = array();

		$dispatcher->listen(
			SampleEvent::class,
			static function ( SampleEvent $event ) use ( &$received ): void {
				$received[] = 'first:' . $event->payload;
			}
		);
		$dispatcher->listen(
			SampleEvent::class,
			static function ( SampleEvent $event ) use ( &$received ): void {
				$received[] = 'second:' . $event->payload;
			}
		);

		$dispatcher->dispatch( new SampleEvent( 'payload' ) );

		self::assertSame( array( 'first:payload', 'second:payload' ), $received );
	}

	public function test_dispatch_only_notifies_listeners_of_the_matching_event_name(): void {
		$dispatcher   = new EventDispatcher();
		$sampleCalled = false;
		$otherCalled  = false;

		$dispatcher->listen(
			SampleEvent::class,
			static function () use ( &$sampleCalled ): void {
				$sampleCalled = true;
			}
		);
		$dispatcher->listen(
			OtherEvent::class,
			static function () use ( &$otherCalled ): void {
				$otherCalled = true;
			}
		);

		$dispatcher->dispatch( new SampleEvent( 'payload' ) );

		self::assertTrue( $sampleCalled );
		self::assertFalse( $otherCalled );
	}
}

final class SampleEvent extends Event {

	public function __construct( public readonly string $payload ) {
	}
}

final class OtherEvent extends Event {

}
