<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Ads;

use Brain\Monkey\Functions;
use DNorteCore\Ads\AdSlotRenderer;
use DNorteCore\Ads\Campaign;
use DNorteCore\Tests\Unit\TestCase;

final class AdSlotRendererTest extends TestCase {

	public function test_it_returns_an_empty_string_without_a_campaign(): void {
		$html = ( new AdSlotRenderer() )->render( null, 'cabecera' );

		self::assertSame( '', $html );
	}

	public function test_it_wraps_html_type_markup_in_the_slot_container_unescaped_with_the_campaign_id(): void {
		Functions\when( 'esc_attr' )->returnArg( 1 );

		$campaign = $this->htmlCampaign( 7, '<script>window.adTag();</script>' );

		$html = ( new AdSlotRenderer() )->render( $campaign, 'top_noticia' );

		self::assertSame(
			'<div class="dnorte-ad dnorte-ad--top_noticia" data-campaign-id="7"><script>window.adTag();</script></div>',
			$html
		);
	}

	public function test_it_renders_an_adsense_unit_from_the_client_and_slot_ids(): void {
		Functions\when( 'esc_attr' )->returnArg( 1 );

		$campaign = new Campaign(
			1,
			'Campaña AdSense',
			'Google',
			Campaign::TYPE_ADSENSE,
			true,
			0,
			array( 'cabecera' ),
			array(),
			null,
			null,
			'',
			'ca-pub-1234567890',
			'9876543210'
		);

		$html = ( new AdSlotRenderer() )->render( $campaign, 'cabecera' );

		self::assertStringContainsString( 'dnorte-ad--cabecera', $html );
		self::assertStringContainsString( 'data-ad-client="ca-pub-1234567890"', $html );
		self::assertStringContainsString( 'data-ad-slot="9876543210"', $html );
		self::assertStringContainsString( 'adsbygoogle', $html );
	}

	public function test_it_renders_an_image_banner_as_a_linked_image(): void {
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );

		$campaign = new Campaign(
			1,
			'Banner de prueba',
			'Anunciante',
			Campaign::TYPE_IMAGE,
			true,
			0,
			array( 'inicio' ),
			array(),
			null,
			null,
			'',
			'',
			'',
			'https://example.com/banner.jpg',
			'https://example.com/promo'
		);

		$html = ( new AdSlotRenderer() )->render( $campaign, 'inicio' );

		self::assertStringContainsString( 'href="https://example.com/promo"', $html );
		self::assertStringContainsString( 'src="https://example.com/banner.jpg"', $html );
		self::assertStringContainsString( 'alt="Banner de prueba"', $html );
		self::assertStringContainsString( 'rel="noopener sponsored"', $html );
	}

	public function test_it_renders_a_video_banner_as_a_linked_autoplaying_video(): void {
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );

		$campaign = new Campaign(
			1,
			'Banner de vídeo',
			'Anunciante',
			Campaign::TYPE_VIDEO,
			true,
			0,
			array( 'inicio' ),
			array(),
			null,
			null,
			'',
			'',
			'',
			'',
			'https://example.com/promo',
			0,
			0,
			array(),
			'',
			'https://example.com/banner.mp4'
		);

		$html = ( new AdSlotRenderer() )->render( $campaign, 'inicio' );

		self::assertStringContainsString( 'href="https://example.com/promo"', $html );
		self::assertStringContainsString( 'src="https://example.com/banner.mp4"', $html );
		self::assertStringContainsString( 'autoplay', $html );
		self::assertStringContainsString( 'muted', $html );
		self::assertStringContainsString( 'loop', $html );
	}

	public function test_it_renders_sponsored_content_with_its_description(): void {
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );

		$campaign = new Campaign(
			1,
			'Nombre interno',
			'Anunciante',
			Campaign::TYPE_SPONSORED,
			true,
			0,
			array( 'top_noticia' ),
			array(),
			null,
			null,
			'',
			'',
			'',
			'https://example.com/foto.jpg',
			'https://example.com/promo',
			0,
			0,
			array(),
			'Descubre la nueva colección'
		);

		$html = ( new AdSlotRenderer() )->render( $campaign, 'top_noticia' );

		self::assertStringContainsString( 'src="https://example.com/foto.jpg"', $html );
		self::assertStringContainsString( 'href="https://example.com/promo"', $html );
		self::assertStringContainsString( 'dnorte-ad__description">Descubre la nueva colección</span>', $html );
	}

	public function test_it_renders_a_gam_slot_with_parsed_sizes(): void {
		Functions\when( 'esc_attr' )->returnArg( 1 );

		$campaign = new Campaign(
			5,
			'Campaña GAM',
			'Anunciante',
			Campaign::TYPE_GAM,
			true,
			0,
			array( 'cabecera' ),
			array(),
			null,
			null,
			'',
			'',
			'',
			'',
			'',
			0,
			0,
			array(),
			'',
			'',
			'/1234567/diariodelnorte/cabecera',
			'728x90, 970x250'
		);

		$html = ( new AdSlotRenderer() )->render( $campaign, 'cabecera' );

		self::assertStringContainsString( 'id="dnorte-gam-5"', $html );
		// wp_json_encode() escapa las barras ("\/") por defecto — JSON válido, sin
		// efecto en cómo lo interpreta el navegador.
		self::assertStringContainsString( '\/1234567\/diariodelnorte\/cabecera', $html );
		self::assertStringContainsString( '[[728,90],[970,250]]', $html );
		self::assertStringContainsString( 'googletag.defineSlot', $html );
	}

	public function test_it_renders_nothing_for_a_gam_campaign_without_valid_sizes(): void {
		Functions\when( 'esc_attr' )->returnArg( 1 );

		$campaign = new Campaign(
			5,
			'Campaña GAM',
			'Anunciante',
			Campaign::TYPE_GAM,
			true,
			0,
			array( 'cabecera' ),
			array(),
			null,
			null,
			'',
			'',
			'',
			'',
			'',
			0,
			0,
			array(),
			'',
			'',
			'/1234567/diariodelnorte/cabecera',
			''
		);

		$html = ( new AdSlotRenderer() )->render( $campaign, 'cabecera' );

		self::assertSame( '<div class="dnorte-ad dnorte-ad--cabecera" data-campaign-id="5"></div>', $html );
	}

	private function htmlCampaign( int $id, string $html ): Campaign {
		return new Campaign( $id, 'Campaña', 'Anunciante', Campaign::TYPE_HTML, true, 0, array( 'top_noticia' ), array(), null, null, $html, '', '' );
	}
}
