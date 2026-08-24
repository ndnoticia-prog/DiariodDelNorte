<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Ads;

use DNorteCore\Ads\Campaign;
use DNorteCore\Ads\CampaignReportPdfRenderer;
use WP_UnitTestCase;

final class CampaignReportPdfRendererTest extends WP_UnitTestCase {

	public function test_render_produces_a_valid_pdf_with_an_embedded_evidence_photo(): void {
		$attachmentId = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );

		$campaign = $this->campaign( evidenceIds: array( $attachmentId ) );

		$pdf = ( new CampaignReportPdfRenderer() )->render( $campaign, array( 'Cabecera' ), 'HTML/banner propio', 'Activa' );

		self::assertStringStartsWith( '%PDF-', $pdf );
		self::assertGreaterThan( 1000, strlen( $pdf ) );
	}

	public function test_render_works_without_any_evidence(): void {
		$campaign = $this->campaign( evidenceIds: array() );

		$pdf = ( new CampaignReportPdfRenderer() )->render( $campaign, array( 'Cabecera' ), 'HTML/banner propio', 'Activa' );

		self::assertStringStartsWith( '%PDF-', $pdf );
	}

	public function test_render_falls_back_to_a_text_link_for_non_image_evidence(): void {
		$pdfAttachmentId = self::factory()->attachment->create_object(
			'contrato.pdf',
			0,
			array(
				'post_mime_type' => 'application/pdf',
				'post_type'      => 'attachment',
			)
		);

		$campaign = $this->campaign( evidenceIds: array( $pdfAttachmentId ) );

		$pdf = ( new CampaignReportPdfRenderer() )->render( $campaign, array( 'Cabecera' ), 'HTML/banner propio', 'Activa' );

		self::assertStringStartsWith( '%PDF-', $pdf );
	}

	/**
	 * @param list<int> $evidenceIds
	 */
	private function campaign( array $evidenceIds ): Campaign {
		return new Campaign(
			1,
			'Campaña de prueba',
			'Anunciante de prueba',
			Campaign::TYPE_HTML,
			true,
			0,
			array( 'cabecera' ),
			array(),
			'2026-01-01 00:00:00',
			'2026-12-31 23:59:59',
			'<div>anuncio</div>',
			'',
			'',
			'',
			'',
			767,
			1,
			$evidenceIds
		);
	}
}
