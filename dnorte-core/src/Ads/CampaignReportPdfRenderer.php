<?php
/**
 * Construye el PDF real de "Generar informe" (Ads\AdsAdminPage::renderReportView()
 * ya tenía una vista imprimible vía `window.print()` desde v0.1.0-alpha.14/15;
 * esta clase la complementa con un PDF descargable de verdad, con el logo de
 * Diario del Norte y la foto de evidencia embebidos, no solo enlazados) —
 * pedido explícito del cliente tras ver la vista imprimible. Usa dompdf
 * (primera dependencia de producción real de la plataforma, ver
 * tools/build/package.sh) en vez de depender del "Imprimir → Guardar como PDF"
 * del navegador del usuario.
 *
 * El logo y cada foto de evidencia se embeben como `data:` URI en base64
 * directamente en el HTML: dompdf corre en el servidor, sin sesión de
 * navegador ni cookies, así que una `<img src="https://.../wp-content/...">`
 * normal le obligaría a volver a descargar cada imagen por HTTP (más lento,
 * y roto en un sitio detrás de auth básica o IP allowlist en desarrollo) —
 * por eso también `Options::setIsRemoteEnabled(false)`: si algún campo llegara
 * a colar una URL remota (no debería, pero por si acaso) dompdf la ignora en
 * vez de intentar descargarla.
 *
 * Sin test unitario: todo lo que hace (leer un adjunto real de la Biblioteca
 * de medios, generar bytes de PDF con una librería real) requiere WordPress y
 * un archivo real en disco — igual que Migrator/Installer, se prueba a nivel
 * de integración (ver tests/Integration/Ads/CampaignReportPdfRendererTest.php)
 * y en el navegador real durante la verificación en vivo.
 *
 * @package DNorteCore\Ads
 */

declare(strict_types=1);

namespace DNorteCore\Ads;

use Dompdf\Dompdf;
use Dompdf\Options;

final class CampaignReportPdfRenderer {

	/**
	 * @param list<string> $zoneNames Etiquetas de espacio ya resueltas
	 *                                  (config/ads.php ads.slots), no las claves
	 *                                  crudas — así esta clase no necesita Config
	 *                                  para nada más que esto.
	 */
	public function render( Campaign $campaign, array $zoneNames, string $typeLabel, string $statusLabel ): string {
		$options = new Options();
		$options->setIsRemoteEnabled( false );
		$options->setDefaultFont( 'DejaVu Sans' ); // La fuente por defecto de dompdf (Helvetica) no trae tildes/eñes.

		$dompdf = new Dompdf( $options );
		$dompdf->loadHtml( $this->buildHtml( $campaign, $zoneNames, $typeLabel, $statusLabel ) );
		$dompdf->setPaper( 'letter', 'portrait' );
		$dompdf->render();

		/** @var string $output dompdf::output() solo devuelve null si render() nunca corrió. */
		$output = $dompdf->output();

		return $output;
	}

	/**
	 * @param list<string> $zoneNames
	 */
	private function buildHtml( Campaign $campaign, array $zoneNames, string $typeLabel, string $statusLabel ): string {
		$rows = array(
			array( __( 'Anunciante', 'dnorte-core' ), $campaign->advertiser ),
			array( __( 'Tipo', 'dnorte-core' ), $typeLabel ),
			array( __( 'Estado', 'dnorte-core' ), $statusLabel ),
			array( __( 'Zonas', 'dnorte-core' ), implode( ', ', $zoneNames ) ),
			array( __( 'Empieza', 'dnorte-core' ), $campaign->startsAt !== null ? $this->displayDatetime( $campaign->startsAt ) : __( 'Sin definir', 'dnorte-core' ) ),
			array( __( 'Termina', 'dnorte-core' ), $campaign->endsAt !== null ? $this->displayDatetime( $campaign->endsAt ) : __( 'Sin definir', 'dnorte-core' ) ),
			array( __( 'Impresiones', 'dnorte-core' ), number_format_i18n( $campaign->impressions ) ),
			array( __( 'Clics', 'dnorte-core' ), number_format_i18n( $campaign->clicks ) ),
			array( __( 'CTR', 'dnorte-core' ), number_format_i18n( $campaign->ctr(), 2 ) . '%' ),
		);

		$rowsHtml = '';
		foreach ( $rows as $row ) {
			$rowsHtml .= sprintf( '<tr><th>%s</th><td>%s</td></tr>', esc_html( $row[0] ), esc_html( $row[1] ) );
		}

		return sprintf(
			'<html><head><meta charset="utf-8" /><style>%1$s</style></head><body>
				<img class="logo" src="data:image/png;base64,%2$s" alt="Diario del Norte" />
				<h1>%3$s</h1>
				<h2 class="campaign-name">%4$s</h2>
				<table>%5$s</table>
				<h2>%6$s</h2>
				%7$s
				<p class="meta">%8$s</p>
			</body></html>',
			$this->css(),
			$this->logoBase64(),
			esc_html__( 'Informe de campaña', 'dnorte-core' ),
			esc_html( $campaign->name ),
			$rowsHtml,
			esc_html__( 'Evidencia', 'dnorte-core' ),
			$this->evidenceHtml( $campaign->evidenceIds ),
			esc_html(
				sprintf(
					/* translators: %s: fecha y hora de generación del informe. */
					__( 'Generado el %s', 'dnorte-core' ),
					date_i18n( 'j \d\e F \d\e Y, H:i' )
				)
			)
		);
	}

	private function css(): string {
		return '
			body { font-family: "DejaVu Sans", sans-serif; color: #1a1a1a; font-size: 12px; }
			.logo { display: block; max-width: 240px; margin-bottom: 18px; }
			h1 { font-size: 19px; margin: 0 0 2px; }
			h2.campaign-name { font-size: 14px; font-weight: normal; color: #5c5c5c; margin: 0 0 18px; }
			h2 { font-size: 14px; margin: 22px 0 8px; }
			table { width: 100%; border-collapse: collapse; }
			th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e2e0db; font-size: 12px; }
			th { width: 150px; color: #5c5c5c; font-weight: 600; }
			.dnorte-pdf-evidence img { max-width: 100%; max-height: 320px; margin-bottom: 12px; border: 1px solid #e2e0db; }
			.meta { color: #8a8a8a; font-size: 10px; margin-top: 26px; }
		';
	}

	private function logoBase64(): string {
		// DNORTE_CORE_DIR se define en dnorte-core.php, fuera de src/ — PHPStan no lo ve
		// sin esta guarda (mismo idiomatismo que CoreServiceProvider/SystemStatusController
		// usan para DNORTE_CORE_VERSION).
		if ( ! defined( 'DNORTE_CORE_DIR' ) ) {
			return '';
		}

		$path = DNORTE_CORE_DIR . '/assets/images/dnorte-logo.png';

		if ( ! is_readable( $path ) ) {
			return '';
		}

		return base64_encode( (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- ruta local propia del plugin (no una URL remota) codificada como data: URI para el PDF, no ofuscación.
	}

	/**
	 * @param list<int> $evidenceIds
	 */
	private function evidenceHtml( array $evidenceIds ): string {
		if ( $evidenceIds === array() ) {
			return '<p>' . esc_html__( 'Sin evidencia adjunta.', 'dnorte-core' ) . '</p>';
		}

		$html = '';

		foreach ( $evidenceIds as $attachmentId ) {
			$html .= $this->evidenceItemHtml( $attachmentId );
		}

		return $html;
	}

	private function evidenceItemHtml( int $attachmentId ): string {
		if ( ! wp_attachment_is_image( $attachmentId ) ) {
			$url = wp_get_attachment_url( $attachmentId );

			if ( ! is_string( $url ) || $url === '' ) {
				return '';
			}

			$title = get_the_title( $attachmentId );

			return sprintf( '<p><a href="%s">%s</a></p>', esc_url( $url ), esc_html( $title !== '' ? $title : $url ) );
		}

		$filePath = $this->evidenceImageFilePath( $attachmentId );

		if ( $filePath === null ) {
			return '';
		}

		$mime = get_post_mime_type( $attachmentId );
		$mime = is_string( $mime ) && $mime !== '' ? $mime : 'image/jpeg';

		return sprintf(
			'<div class="dnorte-pdf-evidence"><img src="data:%s;base64,%s" alt="%s" /></div>',
			esc_attr( $mime ),
			base64_encode( (string) file_get_contents( $filePath ) ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- ruta local ya resuelta en disco (evidenceImageFilePath(), no una URL remota) codificada como data: URI para el PDF, no ofuscación.
			esc_attr( get_the_title( $attachmentId ) )
		);
	}

	/**
	 * Usa la variante "large" (máx. 1024px, generada por WordPress al subir la
	 * imagen) en vez del archivo original: la foto de evidencia que sube el
	 * equipo suele venir directo de un móvil (varios MB), y embeberla a tamaño
	 * completo en base64 infla el PDF sin ninguna ganancia visual a este
	 * tamaño de impresión. Si no existe (imagen ya pequeña, sin intermedio
	 * "large" generado), cae al archivo original.
	 */
	private function evidenceImageFilePath( int $attachmentId ): ?string {
		$src = wp_get_attachment_image_src( $attachmentId, 'large' );

		if ( $src === false ) {
			$path = get_attached_file( $attachmentId );

			return is_string( $path ) && is_readable( $path ) ? $path : null;
		}

		$uploadDir = wp_get_upload_dir();
		$url       = $src[0];

		if ( ! str_starts_with( $url, $uploadDir['baseurl'] ) ) {
			return null;
		}

		$path = $uploadDir['basedir'] . substr( $url, strlen( $uploadDir['baseurl'] ) );

		return is_readable( $path ) ? $path : null;
	}

	private function displayDatetime( string $mysqlDatetime ): string {
		$timestamp = strtotime( $mysqlDatetime . ' UTC' );

		return $timestamp !== false ? date_i18n( 'j M Y, H:i', $timestamp ) : $mysqlDatetime;
	}
}
