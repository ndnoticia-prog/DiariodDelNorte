<?php
/**
 * Panel "Newsletter": total de suscriptores y los últimos 200 correos captados
 * desde el formulario de portada. Solo lectura — sin ningún formulario que
 * verificar con nonce, mismo criterio que Analytics\AnalyticsAdminPage.
 *
 * Sin exportar a CSV en esta primera versión: si el volumen de suscriptores
 * lo justifica más adelante, es una ampliación aislada a este archivo.
 *
 * @package DNorteCore\Newsletter
 */

declare(strict_types=1);

namespace DNorteCore\Newsletter;

use DNorteCore\Admin\AdminPage;
use DNorteCore\Admin\Contracts\RegistersAdminPages;
use DNorteCore\Newsletter\Subscribers\NewsletterSubscriberRepository;

final class NewsletterAdminPage implements RegistersAdminPages {

	private const CAPABILITY = 'edit_others_posts';

	public function __construct( private readonly NewsletterSubscriberRepository $subscribers ) {
	}

	/**
	 * @return list<AdminPage>
	 */
	public function adminPages(): array {
		return array(
			new AdminPage(
				'dnorte-newsletter',
				__( 'Newsletter', 'dnorte-core' ),
				__( 'Newsletter', 'dnorte-core' ),
				self::CAPABILITY,
				$this->render( ... ),
				10,
				'dashicons-email-alt'
			),
		);
	}

	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'dnorte-core' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Newsletter', 'dnorte-core' ) . '</h1>';

		printf(
			'<p>%s</p>',
			esc_html(
				sprintf(
					/* translators: %d: número total de suscriptores. */
					__( 'Suscriptores totales: %d', 'dnorte-core' ),
					$this->subscribers->count()
				)
			)
		);

		$this->renderList();

		echo '</div>';
	}

	private function renderList(): void {
		$latest = $this->subscribers->latest();

		if ( $latest === array() ) {
			echo '<p>' . esc_html__( 'Todavía no hay suscriptores.', 'dnorte-core' ) . '</p>';

			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Correo', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Fecha de suscripción', 'dnorte-core' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $latest as $subscriber ) {
			echo '<tr><td>' . esc_html( $subscriber['email'] ) . '</td><td>' . esc_html( $subscriber['subscribed_at'] ) . '</td></tr>';
		}

		echo '</tbody></table>';
	}
}
