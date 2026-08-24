<?php
/**
 * `POST /wp-json/dnorte/v1/newsletter/subscribe` — recibe la suscripción del
 * formulario de portada (dnorte-theme, template-parts/blocks/newsletter.php).
 *
 * Sin dependencias en el constructor a propósito, mismo motivo documentado en
 * Analytics\PageviewController: NewsletterSubscriberRepository depende en
 * cadena de DatabaseManager → wpdb, inexistente en el proceso de pruebas
 * unitarias — handle() arma el repositorio a mano con `global $wpdb`.
 *
 * @package DNorteCore\Newsletter
 */

declare(strict_types=1);

namespace DNorteCore\Newsletter;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Newsletter\Subscribers\NewsletterSubscriberRepository;
use DNorteCore\RestApi\Contracts\RegistersRoutes;
use DNorteCore\Routing\Router;
use WP_REST_Request;
use WP_REST_Response;

final class NewsletterController implements RegistersRoutes {

	public function registerRoutes( Router $router ): void {
		$router->register(
			'dnorte/v1',
			'/newsletter/subscribe',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'email' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
	}

	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$email = sanitize_email( (string) $request->get_param( 'email' ) );

		// is_email() devuelve el correo saneado (string) si es válido, o false si no
		// — nunca un bool "puro" en el caso de éxito, de ahí el === false explícito
		// en vez de negarlo directamente.
		if ( is_email( $email ) === false ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Escribe un correo electrónico válido.', 'dnorte-core' ) ),
				400
			);
		}

		global $wpdb;
		$repository = new NewsletterSubscriberRepository( new DatabaseManager( $wpdb ) );
		$isNew      = $repository->subscribe( $email );

		return new WP_REST_Response(
			array(
				'message' => $isNew
					? __( '¡Listo! Ya estás suscrito.', 'dnorte-core' )
					: __( 'Ese correo ya estaba suscrito.', 'dnorte-core' ),
			),
			200
		);
	}
}
