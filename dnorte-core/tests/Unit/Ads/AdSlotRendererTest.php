<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Ads;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;
use DNorteCore\Ads\Ad;
use DNorteCore\Ads\AdSlotRenderer;
use DNorteCore\Tests\Unit\TestCase;

final class AdSlotRendererTest extends TestCase {

	public function test_it_returns_an_empty_string_without_an_ad(): void {
		$html = ( new AdSlotRenderer() )->render( null, 'cabecera', $this->now() );

		self::assertSame( '', $html );
	}

	public function test_it_returns_an_empty_string_for_an_inactive_ad(): void {
		Functions\when( 'esc_attr' )->returnArg( 1 );

		$ad = new Ad( 1, 'cabecera', '<script>ad</script>', false, null, null );

		$html = ( new AdSlotRenderer() )->render( $ad, 'cabecera', $this->now() );

		self::assertSame( '', $html );
	}

	public function test_it_wraps_the_raw_ad_html_in_the_slot_container_unescaped(): void {
		Functions\when( 'esc_attr' )->returnArg( 1 );

		$ad = new Ad( 1, 'top_noticia', '<script>window.adTag();</script>', true, null, null );

		$html = ( new AdSlotRenderer() )->render( $ad, 'top_noticia', $this->now() );

		self::assertSame(
			'<div class="dnorte-ad dnorte-ad--top_noticia"><script>window.adTag();</script></div>',
			$html
		);
	}

	private function now(): DateTimeImmutable {
		return new DateTimeImmutable( '2026-08-23 12:00:00', new DateTimeZone( 'UTC' ) );
	}
}
