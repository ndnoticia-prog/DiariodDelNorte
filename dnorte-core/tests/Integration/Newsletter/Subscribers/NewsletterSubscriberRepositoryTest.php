<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Newsletter\Subscribers;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Newsletter\Subscribers\NewsletterSubscriberRepository;
use WP_UnitTestCase;

final class NewsletterSubscriberRepositoryTest extends WP_UnitTestCase {

	public function test_subscribe_adds_a_new_email_and_returns_true(): void {
		global $wpdb;
		$repository = new NewsletterSubscriberRepository( new DatabaseManager( $wpdb ) );

		$result = $repository->subscribe( 'lectora@example.com' );

		self::assertTrue( $result );
		self::assertNotNull( $repository->find( 'lectora@example.com' ) );
	}

	public function test_subscribe_is_idempotent_and_returns_false_for_an_email_already_subscribed(): void {
		global $wpdb;
		$repository = new NewsletterSubscriberRepository( new DatabaseManager( $wpdb ) );

		$repository->subscribe( 'lectora@example.com' );
		$second = $repository->subscribe( 'lectora@example.com' );

		self::assertFalse( $second );
		self::assertSame( 1, $repository->count() );
	}

	public function test_latest_returns_the_most_recently_subscribed_first(): void {
		global $wpdb;
		$repository = new NewsletterSubscriberRepository( new DatabaseManager( $wpdb ) );

		$repository->subscribe( 'primero@example.com' );
		$repository->subscribe( 'segundo@example.com' );

		// subscribed_at solo tiene precisión de segundo (gmdate('Y-m-d H:i:s')) — sin
		// esto ambas filas podrían quedar con el mismo valor y el orden no sería
		// determinista. Se retrasa la primera en vez de sleep()-ear la prueba.
		$wpdb->update(
			$wpdb->prefix . 'dnorte_newsletter_subscribers',
			array( 'subscribed_at' => gmdate( 'Y-m-d H:i:s', time() - 3600 ) ),
			array( 'email' => 'primero@example.com' )
		);

		$latest = $repository->latest();

		self::assertSame( 'segundo@example.com', $latest[0]['email'] );
		self::assertSame( 'primero@example.com', $latest[1]['email'] );
	}
}
