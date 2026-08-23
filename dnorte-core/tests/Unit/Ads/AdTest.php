<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Ads;

use DateTimeImmutable;
use DateTimeZone;
use DNorteCore\Ads\Ad;
use DNorteCore\Tests\Unit\TestCase;

final class AdTest extends TestCase {

	public function test_a_disabled_ad_is_never_active(): void {
		$ad = new Ad( 1, 'cabecera', '<div>x</div>', false, null, null );

		self::assertFalse( $ad->isActiveAt( $this->now() ) );
	}

	public function test_an_enabled_ad_without_dates_is_always_active(): void {
		$ad = new Ad( 1, 'cabecera', '<div>x</div>', true, null, null );

		self::assertTrue( $ad->isActiveAt( $this->now() ) );
	}

	public function test_an_ad_is_inactive_before_its_start_date(): void {
		$ad = new Ad( 1, 'cabecera', '<div>x</div>', true, '2026-09-01 00:00:00', null );

		self::assertFalse( $ad->isActiveAt( $this->now() ) );
	}

	public function test_an_ad_is_inactive_after_its_end_date(): void {
		$ad = new Ad( 1, 'cabecera', '<div>x</div>', true, null, '2026-08-01 00:00:00' );

		self::assertFalse( $ad->isActiveAt( $this->now() ) );
	}

	public function test_an_ad_is_active_within_its_date_range(): void {
		$ad = new Ad( 1, 'cabecera', '<div>x</div>', true, '2026-08-01 00:00:00', '2026-09-01 00:00:00' );

		self::assertTrue( $ad->isActiveAt( $this->now() ) );
	}

	private function now(): DateTimeImmutable {
		return new DateTimeImmutable( '2026-08-23 12:00:00', new DateTimeZone( 'UTC' ) );
	}
}
