<?php
/**
 * @package DNorteTheme\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteTheme\Tests\Integration\Content;

use DNorteTheme\Content\DefaultContentSeeder;
use WP_UnitTestCase;

final class DefaultContentSeederTest extends WP_UnitTestCase {

	private const EXPECTED_SLUGS = array(
		'la-guajira',
		'politica',
		'judiciales',
		'caribe',
		'nacion',
		'mundo',
		'opinion',
		'editorial',
		'edicion-impresa',
		'sociales',
		'oraculos',
		'multimedia',
		'especiales',
		'edictos',
		'negocios',
		'deportes',
		'entretenimiento',
		'notas-rosas',
		'tecnologia',
	);

	public function test_seed_creates_all_nineteen_categories(): void {
		( new DefaultContentSeeder() )->seed();

		foreach ( self::EXPECTED_SLUGS as $slug ) {
			self::assertInstanceOf( \WP_Term::class, get_term_by( 'slug', $slug, 'category' ), "Falta la categoría \"{$slug}\"." );
		}
	}

	public function test_seed_creates_and_assigns_a_primary_menu_with_inicio_and_mas(): void {
		( new DefaultContentSeeder() )->seed();

		$locations = get_nav_menu_locations();
		self::assertArrayHasKey( 'primary', $locations );

		$items = wp_get_nav_menu_items( $locations['primary'] );
		self::assertNotFalse( $items );

		$titles = array_map( static fn ( $item ) => $item->title, $items );
		self::assertContains( 'Inicio', $titles );
		self::assertContains( 'Más', $titles );
		self::assertContains( 'La Guajira', $titles );
		self::assertContains( 'Tecnología', $titles );

		// Inicio + 10 categorías de primer nivel + "Más" = 12; más las 9 hijas de
		// "Más" = 21 en total.
		self::assertCount( 21, $items );
	}

	public function test_seed_nests_the_more_categories_under_the_mas_parent_item(): void {
		( new DefaultContentSeeder() )->seed();

		$locations = get_nav_menu_locations();
		$items     = wp_get_nav_menu_items( $locations['primary'] );

		$masItem = null;
		foreach ( $items as $item ) {
			if ( $item->title === 'Más' ) {
				$masItem = $item;
				break;
			}
		}

		self::assertNotNull( $masItem );

		$children = array_filter( $items, static fn ( $item ) => (int) $item->menu_item_parent === $masItem->ID );
		self::assertCount( 9, $children );
	}

	public function test_seed_is_idempotent(): void {
		$seeder = new DefaultContentSeeder();
		$seeder->seed();
		$seeder->seed();

		$categories = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
			)
		);
		// +1 por "Uncategorized", la categoría por defecto de WordPress.
		self::assertCount( count( self::EXPECTED_SLUGS ) + 1, $categories );

		$locations = get_nav_menu_locations();
		$items     = wp_get_nav_menu_items( $locations['primary'] );
		self::assertCount( 21, $items );
	}

	public function test_seed_never_touches_a_primary_menu_that_already_exists(): void {
		$existingMenuId = wp_create_nav_menu( 'Menú del cliente' );
		wp_update_nav_menu_item(
			$existingMenuId,
			0,
			array(
				'menu-item-title'  => 'Portada',
				'menu-item-url'    => home_url( '/' ),
				'menu-item-type'   => 'custom',
				'menu-item-status' => 'publish',
			)
		);
		set_theme_mod( 'nav_menu_locations', array( 'primary' => $existingMenuId ) );

		( new DefaultContentSeeder() )->seed();

		$locations = get_nav_menu_locations();
		self::assertSame( $existingMenuId, $locations['primary'] );

		$items = wp_get_nav_menu_items( $existingMenuId );
		self::assertCount( 1, $items );
		self::assertSame( 'Portada', $items[0]->title );
	}

	public function test_seed_does_not_duplicate_a_category_that_already_exists(): void {
		wp_insert_term( 'La Guajira (ya existía)', 'category', array( 'slug' => 'la-guajira' ) );

		( new DefaultContentSeeder() )->seed();

		$matches = get_terms(
			array(
				'taxonomy'   => 'category',
				'slug'       => 'la-guajira',
				'hide_empty' => false,
			)
		);

		self::assertCount( 1, $matches );
		self::assertSame( 'La Guajira (ya existía)', $matches[0]->name );
	}
}
