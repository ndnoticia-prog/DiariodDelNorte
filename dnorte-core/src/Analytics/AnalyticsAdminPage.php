<?php
/**
 * Panel "Analítica": vistas totales (24h/7d/30d) y los artículos más leídos en la
 * ventana configurada (`analytics.top_articles_window_days`). Solo lectura — a
 * diferencia del panel de turnos, no tiene ningún formulario que verificar con
 * nonce.
 *
 * @package DNorteCore\Analytics
 */

declare(strict_types=1);

namespace DNorteCore\Analytics;

use DateTimeImmutable;
use DateTimeZone;
use DNorteCore\Admin\AdminPage;
use DNorteCore\Admin\Contracts\RegistersAdminPages;
use DNorteCore\Analytics\Pageviews\PageviewRepository;
use DNorteCore\Config\Config;

final class AnalyticsAdminPage implements RegistersAdminPages {

	private const CAPABILITY = 'edit_others_posts';

	public function __construct(
		private readonly PageviewRepository $pageviews,
		private readonly Config $config
	) {
	}

	/**
	 * @return list<AdminPage>
	 */
	public function adminPages(): array {
		return array(
			new AdminPage(
				'dnorte-analitica',
				__( 'Analítica', 'dnorte-core' ),
				__( 'Analítica', 'dnorte-core' ),
				self::CAPABILITY,
				$this->render( ... ),
				10,
				'dashicons-chart-bar'
			),
		);
	}

	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'dnorte-core' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Analítica', 'dnorte-core' ) . '</h1>';

		$this->renderTotals();
		$this->renderTopArticles();

		echo '</div>';
	}

	private function renderTotals(): void {
		$now = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		$rows = array(
			array( __( 'Últimas 24 horas', 'dnorte-core' ), $this->pageviews->totalSince( $now->modify( '-1 day' ) ) ),
			array( __( 'Últimos 7 días', 'dnorte-core' ), $this->pageviews->totalSince( $now->modify( '-7 days' ) ) ),
			array( __( 'Últimos 30 días', 'dnorte-core' ), $this->pageviews->totalSince( $now->modify( '-30 days' ) ) ),
		);

		echo '<h2>' . esc_html__( 'Vistas totales', 'dnorte-core' ) . '</h2>';
		echo '<table class="widefat striped"><tbody>';

		foreach ( $rows as [ $label, $value ] ) {
			echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( number_format_i18n( $value ) ) . '</td></tr>';
		}

		echo '</tbody></table>';
	}

	private function renderTopArticles(): void {
		/** @var int $windowDays */
		$windowDays = $this->config->get( 'analytics.top_articles_window_days', 7 );

		$since = ( new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )->modify( "-{$windowDays} days" );
		$top   = $this->pageviews->topArticlesSince( $since, 10 );

		echo '<h2>';
		printf(
			/* translators: %d: número de días de la ventana. */
			esc_html__( 'Artículos más vistos (últimos %d días)', 'dnorte-core' ),
			absint( $windowDays )
		);
		echo '</h2>';

		if ( $top === array() ) {
			echo '<p>' . esc_html__( 'Todavía no hay datos de visitas en este periodo.', 'dnorte-core' ) . '</p>';

			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Artículo', 'dnorte-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Vistas', 'dnorte-core' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $top as $row ) {
			$this->renderTopArticleRow( $row['post_id'], $row['views'] );
		}

		echo '</tbody></table>';
	}

	private function renderTopArticleRow( int $postId, int $views ): void {
		$title = get_the_title( $postId );
		$url   = get_permalink( $postId );
		$label = $title !== '' ? $title : sprintf(
			/* translators: %d: id del artículo. */
			__( 'Artículo #%d', 'dnorte-core' ),
			$postId
		);

		echo '<tr><td>';

		if ( is_string( $url ) && $url !== '' ) {
			printf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $label ) );
		} else {
			echo esc_html( $label );
		}

		echo '</td><td>' . esc_html( (string) $views ) . '</td></tr>';
	}
}
