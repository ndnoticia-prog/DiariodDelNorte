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

	public function test_it_wraps_html_type_markup_in_the_slot_container_unescaped(): void {
		Functions\when( 'esc_attr' )->returnArg( 1 );

		$campaign = $this->htmlCampaign( '<script>window.adTag();</script>' );

		$html = ( new AdSlotRenderer() )->render( $campaign, 'top_noticia' );

		self::assertSame(
			'<div class="dnorte-ad dnorte-ad--top_noticia"><script>window.adTag();</script></div>',
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

	private function htmlCampaign( string $html ): Campaign {
		return new Campaign( 1, 'Campaña', 'Anunciante', Campaign::TYPE_HTML, true, 0, array( 'top_noticia' ), array(), null, null, $html, '', '' );
	}
}
