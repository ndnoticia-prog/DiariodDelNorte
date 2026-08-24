<?php
/**
 * Cierra el hueco documentado en la suite unitaria (NewsletterControllerTest ahí
 * solo cubre registerRoutes(), no handle()).
 *
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Newsletter;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Newsletter\Subscribers\NewsletterSubscriberRepository;
use WP_REST_Request;
use WP_UnitTestCase;

final class NewsletterControllerTest extends WP_UnitTestCase {

	public function test_it_subscribes_a_valid_email(): void {
		do_action( 'rest_api_init' );

		$request = new WP_REST_Request( 'POST', '/dnorte/v1/newsletter/subscribe' );
		$request->set_param( 'email', 'lectora@example.com' );

		$response = rest_get_server()->dispatch( $request );

		self::assertSame( 200, $response->get_status() );

		global $wpdb;
		$repository = new NewsletterSubscriberRepository( new DatabaseManager( $wpdb ) );
		self::assertNotNull( $repository->find( 'lectora@example.com' ) );
	}

	public function test_it_rejects_an_invalid_email_without_storing_anything(): void {
		do_action( 'rest_api_init' );

		$request = new WP_REST_Request( 'POST', '/dnorte/v1/newsletter/subscribe' );
		$request->set_param( 'email', 'no-es-un-correo' );

		$response = rest_get_server()->dispatch( $request );

		self::assertSame( 400, $response->get_status() );

		global $wpdb;
		$repository = new NewsletterSubscriberRepository( new DatabaseManager( $wpdb ) );
		self::assertSame( 0, $repository->count() );
	}

	public function test_it_does_not_fail_when_the_same_email_subscribes_twice(): void {
		do_action( 'rest_api_init' );

		$request = new WP_REST_Request( 'POST', '/dnorte/v1/newsletter/subscribe' );
		$request->set_param( 'email', 'lectora@example.com' );
		rest_get_server()->dispatch( $request );

		$second = new WP_REST_Request( 'POST', '/dnorte/v1/newsletter/subscribe' );
		$second->set_param( 'email', 'lectora@example.com' );
		$response = rest_get_server()->dispatch( $second );

		self::assertSame( 200, $response->get_status() );

		global $wpdb;
		$repository = new NewsletterSubscriberRepository( new DatabaseManager( $wpdb ) );
		self::assertSame( 1, $repository->count() );
	}
}
