<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Ads;

use DNorteCore\Ads\AdRepository;
use DNorteCore\Database\DatabaseManager;
use WP_UnitTestCase;

final class AdRepositoryTest extends WP_UnitTestCase {

	public function test_for_slot_returns_null_without_an_ad(): void {
		global $wpdb;
		$repository = new AdRepository( new DatabaseManager( $wpdb ) );

		self::assertNull( $repository->forSlot( 'cabecera_' . uniqid() ) );
	}

	public function test_upsert_creates_and_then_replaces_the_single_ad_for_a_slot(): void {
		global $wpdb;
		$repository = new AdRepository( new DatabaseManager( $wpdb ) );
		$slot       = 'cabecera_' . uniqid();

		$repository->upsert( $slot, '<div>Primer anuncio</div>', true, null, null );
		$first = $repository->forSlot( $slot );

		self::assertNotNull( $first );
		self::assertSame( '<div>Primer anuncio</div>', $first->html );
		self::assertTrue( $first->enabled );

		$repository->upsert( $slot, '<div>Anuncio actualizado</div>', false, '2026-01-01 00:00:00', '2026-12-31 23:59:59' );
		$second = $repository->forSlot( $slot );

		self::assertNotNull( $second );
		self::assertSame( $first->id, $second->id, 'Debe reemplazar la misma fila, no crear una segunda.' );
		self::assertSame( '<div>Anuncio actualizado</div>', $second->html );
		self::assertFalse( $second->enabled );
		self::assertSame( '2026-01-01 00:00:00', $second->startsAt );
		self::assertSame( '2026-12-31 23:59:59', $second->endsAt );
	}

	public function test_clear_removes_the_ad_for_a_slot(): void {
		global $wpdb;
		$repository = new AdRepository( new DatabaseManager( $wpdb ) );
		$slot       = 'cabecera_' . uniqid();

		$repository->upsert( $slot, '<div>x</div>', true, null, null );
		self::assertNotNull( $repository->forSlot( $slot ) );

		$repository->clear( $slot );

		self::assertNull( $repository->forSlot( $slot ) );
	}
}
