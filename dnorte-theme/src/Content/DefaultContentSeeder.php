<?php
/**
 * Siembra, una única vez por sitio, las categorías y el menú principal que la
 * maqueta real de portada del cliente exige (LA GUAJIRA/POLÍTICA/JUDICIALES/
 * CARIBE/NACIÓN/MUNDO/OPINIÓN/EDITORIAL/EDICIÓN IMPRESA/SOCIALES, más "MÁS" con
 * nueve subcategorías) — así el sitio no depende de que alguien las cree a mano
 * en wp-admin antes de que la portada tenga dónde enlazar ni qué mostrar en cada
 * bloque de HomeContentProvider.
 *
 * Guardada por una opción (`SEEDED_OPTION`), nunca por `after_switch_theme`: ese
 * hook solo dispara al CAMBIAR de tema activo, no en una actualización de versión
 * de un tema que ya estaba activo — que es exactamente el caso real de desplegar
 * esta versión sobre un sitio que ya tiene dnorte-theme puesto (ver
 * ThemeServiceProvider::boot()). Por eso se engancha a `after_setup_theme` (corre
 * en cada carga) y es este método el que decide, con la opción, si ya no hay nada
 * que hacer.
 *
 * Nunca pisa nada que ya exista: una categoría se busca por slug antes de
 * crearla, y el menú solo se crea/asigna si la ubicación `primary` no tiene ya un
 * menú asignado — si el equipo editorial ya armó su propio menú, esta clase no
 * vuelve a tocarlo jamás, ni siquiera tras marcar la opción como sembrada.
 *
 * @package DNorteTheme\Content
 */

declare(strict_types=1);

namespace DNorteTheme\Content;

use WP_Term;

final class DefaultContentSeeder {

	private const SEEDED_OPTION = 'dnorte_theme_default_content_seeded';

	private const MENU_NAME = 'Menú principal';

	/**
	 * @var list<array{slug: string, name: string}>
	 */
	private const TOP_CATEGORIES = array(
		array(
			'slug' => 'la-guajira',
			'name' => 'La Guajira',
		),
		array(
			'slug' => 'politica',
			'name' => 'Política',
		),
		array(
			'slug' => 'judiciales',
			'name' => 'Judiciales',
		),
		array(
			'slug' => 'caribe',
			'name' => 'Caribe',
		),
		array(
			'slug' => 'nacion',
			'name' => 'Nación',
		),
		array(
			'slug' => 'mundo',
			'name' => 'Mundo',
		),
		array(
			'slug' => 'opinion',
			'name' => 'Opinión',
		),
		array(
			'slug' => 'editorial',
			'name' => 'Editorial',
		),
		array(
			'slug' => 'edicion-impresa',
			'name' => 'Edición Impresa',
		),
		array(
			'slug' => 'sociales',
			'name' => 'Sociales',
		),
	);

	/**
	 * @var list<array{slug: string, name: string}>
	 */
	private const MORE_CATEGORIES = array(
		array(
			'slug' => 'oraculos',
			'name' => 'Oráculos',
		),
		array(
			'slug' => 'multimedia',
			'name' => 'Multimedia',
		),
		array(
			'slug' => 'especiales',
			'name' => 'Especiales',
		),
		array(
			'slug' => 'edictos',
			'name' => 'Edictos',
		),
		array(
			'slug' => 'negocios',
			'name' => 'Negocios',
		),
		array(
			'slug' => 'deportes',
			'name' => 'Deportes',
		),
		array(
			'slug' => 'entretenimiento',
			'name' => 'Entretenimiento',
		),
		array(
			'slug' => 'notas-rosas',
			'name' => 'Notas Rosas',
		),
		array(
			'slug' => 'tecnologia',
			'name' => 'Tecnología',
		),
	);

	public function seed(): void {
		if ( (bool) get_option( self::SEEDED_OPTION ) ) {
			return;
		}

		$topIds  = $this->ensureCategories( self::TOP_CATEGORIES );
		$moreIds = $this->ensureCategories( self::MORE_CATEGORIES );

		$this->ensureMenu( $topIds, $moreIds );

		update_option( self::SEEDED_OPTION, true );
	}

	/**
	 * @param list<array{slug: string, name: string}> $categories
	 * @return array<string, int> slug => term_id
	 */
	private function ensureCategories( array $categories ): array {
		$ids = array();

		foreach ( $categories as $category ) {
			$existing = get_term_by( 'slug', $category['slug'], 'category' );

			if ( $existing instanceof WP_Term ) {
				$ids[ $category['slug'] ] = $existing->term_id;
				continue;
			}

			$result = wp_insert_term( $category['name'], 'category', array( 'slug' => $category['slug'] ) );

			if ( ! is_wp_error( $result ) ) {
				$ids[ $category['slug'] ] = $result['term_id'];
			}
		}

		return $ids;
	}

	/**
	 * @param array<string, int> $topIds
	 * @param array<string, int> $moreIds
	 */
	private function ensureMenu( array $topIds, array $moreIds ): void {
		$locations = get_nav_menu_locations();

		if ( isset( $locations['primary'] ) && $locations['primary'] > 0 ) {
			return;
		}

		$existingMenu = wp_get_nav_menu_object( self::MENU_NAME );
		$menuId       = $existingMenu instanceof WP_Term ? $existingMenu->term_id : wp_create_nav_menu( self::MENU_NAME );

		if ( is_wp_error( $menuId ) ) {
			return;
		}

		// Un menú con este nombre ya tiene items (creado en un intento anterior, o a
		// mano por alguien con este mismo nombre): no duplicar, solo asignarlo.
		if ( wp_get_nav_menu_items( $menuId ) === array() ) {
			$this->populateMenu( $menuId, $topIds, $moreIds );
		}

		$locations            = get_nav_menu_locations();
		$locations['primary'] = $menuId;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 * @param array<string, int> $topIds
	 * @param array<string, int> $moreIds
	 */
	private function populateMenu( int $menuId, array $topIds, array $moreIds ): void {
		wp_update_nav_menu_item(
			$menuId,
			0,
			array(
				'menu-item-title'  => __( 'Inicio', 'dnorte-theme' ),
				'menu-item-url'    => home_url( '/' ),
				'menu-item-type'   => 'custom',
				'menu-item-status' => 'publish',
			)
		);

		foreach ( self::TOP_CATEGORIES as $category ) {
			$termId = $topIds[ $category['slug'] ] ?? 0;

			if ( $termId === 0 ) {
				continue;
			}

			wp_update_nav_menu_item(
				$menuId,
				0,
				array(
					'menu-item-title'  => $category['name'],
					'menu-item-url'    => get_category_link( $termId ),
					'menu-item-type'   => 'custom',
					'menu-item-status' => 'publish',
				)
			);
		}

		// "Más" no tiene contenido propio (no es una categoría real) — solo agrupa
		// las nueve de abajo en un desplegable; sin destino propio, de ahí el '#'.
		$moreParentId = wp_update_nav_menu_item(
			$menuId,
			0,
			array(
				'menu-item-title'  => __( 'Más', 'dnorte-theme' ),
				'menu-item-url'    => '#',
				'menu-item-type'   => 'custom',
				'menu-item-status' => 'publish',
			)
		);

		if ( is_wp_error( $moreParentId ) ) {
			return;
		}

		foreach ( self::MORE_CATEGORIES as $category ) {
			$termId = $moreIds[ $category['slug'] ] ?? 0;

			if ( $termId === 0 ) {
				continue;
			}

			wp_update_nav_menu_item(
				$menuId,
				0,
				array(
					'menu-item-title'     => $category['name'],
					'menu-item-url'       => get_category_link( $termId ),
					'menu-item-type'      => 'custom',
					'menu-item-status'    => 'publish',
					'menu-item-parent-id' => $moreParentId,
				)
			);
		}
	}
}
