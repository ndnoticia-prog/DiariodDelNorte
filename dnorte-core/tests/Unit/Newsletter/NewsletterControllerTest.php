<?php
/**
 * handle() no se cubre aquí: arma un NewsletterSubscriberRepository real (con
 * `global $wpdb`) y devuelve un WP_REST_Response, ninguno existe fuera de un
 * WordPress real — cerrado por la prueba de integración.
 *
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Newsletter;

use Brain\Monkey\Functions;
use DNorteCore\Newsletter\NewsletterController;
use DNorteCore\Routing\Router;
use DNorteCore\Tests\Unit\TestCase;
use Mockery;

final class NewsletterControllerTest extends TestCase {

	public function test_register_routes_registers_a_public_post_endpoint_requiring_email(): void {
		Functions\expect( 'register_rest_route' )
			->once()
			->with(
				'dnorte/v1',
				'/newsletter/subscribe',
				Mockery::on(
					static function ( array $args ): bool {
						return $args['methods'] === 'POST'
							&& $args['permission_callback'] === '__return_true'
							&& is_callable( $args['callback'] )
							&& $args['args']['email']['required'] === true;
					}
				)
			);

		( new NewsletterController() )->registerRoutes( new Router() );

		$this->addToAssertionCount( 1 );
	}
}
