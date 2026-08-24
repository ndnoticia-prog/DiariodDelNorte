<?php
/**
 * @package DNorteTheme\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteTheme\Tests\Integration\Content;

use DNorteTheme\Content\HomeContentProvider;
use WP_UnitTestCase;

final class HomeContentProviderTest extends WP_UnitTestCase {

	public function test_content_returns_null_hero_and_empty_lists_without_posts(): void {
		$content = ( new HomeContentProvider() )->content();

		self::assertNull( $content['hero'] );
		self::assertSame( array(), $content['heroThumbs'] );
		self::assertSame( array(), $content['aside'] );
		self::assertSame( array(), $content['laGuajiraFeatured'] );
		self::assertSame( array(), $content['laGuajiraList'] );
		self::assertSame( array(), $content['judiciales'] );
		self::assertSame( array(), $content['opinion'] );
		self::assertNull( $content['editorial'] );
		self::assertNull( $content['edicionImpresa'] );
		self::assertSame( array(), $content['mostRead'] );
		self::assertSame( array(), $content['newsGrid'] );
	}

	public function test_content_puts_the_most_recent_post_as_hero(): void {
		self::factory()->post->create(
			array(
				'post_title' => 'Más antiguo',
				'post_date'  => '2020-01-01 00:00:00',
			)
		);
		$newestId = self::factory()->post->create(
			array(
				'post_title' => 'Más reciente',
				'post_date'  => '2024-01-01 00:00:00',
			)
		);

		$content = ( new HomeContentProvider() )->content();

		self::assertNotNull( $content['hero'] );
		self::assertSame( $newestId, $content['hero']->ID );
	}

	public function test_content_splits_the_hero_group_between_hero_and_thumbs_without_overlap(): void {
		self::factory()->post->create_many( 7 );

		$content = ( new HomeContentProvider() )->content();

		self::assertNotNull( $content['hero'] );
		self::assertCount( 6, $content['heroThumbs'] );

		$thumbIds = array_map( static fn ( $post ) => $post->ID, $content['heroThumbs'] );
		self::assertNotContains( $content['hero']->ID, $thumbIds );
	}

	public function test_content_aside_never_repeats_a_post_from_the_hero_group(): void {
		self::factory()->post->create_many( 13 );

		$content = ( new HomeContentProvider() )->content();

		self::assertCount( 6, $content['aside'] );

		$heroGroupIds = array_merge(
			array( $content['hero']->ID ),
			array_map( static fn ( $post ) => $post->ID, $content['heroThumbs'] )
		);
		$asideIds     = array_map( static fn ( $post ) => $post->ID, $content['aside'] );

		self::assertEmpty( array_intersect( $heroGroupIds, $asideIds ) );
	}

	public function test_content_news_grid_excludes_the_hero_group_and_aside_but_not_category_posts(): void {
		self::factory()->post->create_many( 30 );

		$content = ( new HomeContentProvider() )->content();

		$excludedIds = array_merge(
			array( $content['hero']->ID ),
			array_map( static fn ( $post ) => $post->ID, $content['heroThumbs'] ),
			array_map( static fn ( $post ) => $post->ID, $content['aside'] )
		);
		$newsGridIds = array_map( static fn ( $post ) => $post->ID, $content['newsGrid'] );

		self::assertEmpty( array_intersect( $excludedIds, $newsGridIds ) );
		self::assertSame( $excludedIds, $content['newsGridExcluded'] );
	}

	public function test_content_reads_la_guajira_and_judiciales_from_their_own_categories(): void {
		$laGuajiraId  = self::factory()->category->create( array( 'slug' => 'la-guajira' ) );
		$judicialesId = self::factory()->category->create( array( 'slug' => 'judiciales' ) );

		$laGuajiraPost  = self::factory()->post->create( array( 'post_category' => array( $laGuajiraId ) ) );
		$judicialesPost = self::factory()->post->create( array( 'post_category' => array( $judicialesId ) ) );
		self::factory()->post->create(); // Sin categoría — no debe aparecer en ninguna.

		$content = ( new HomeContentProvider() )->content();

		$laGuajiraIds  = array_map(
			static fn ( $post ) => $post->ID,
			array_merge( $content['laGuajiraFeatured'], $content['laGuajiraList'] )
		);
		$judicialesIds = array_map( static fn ( $post ) => $post->ID, $content['judiciales'] );

		self::assertContains( $laGuajiraPost, $laGuajiraIds );
		self::assertContains( $judicialesPost, $judicialesIds );
		self::assertNotContains( $judicialesPost, $laGuajiraIds );
	}

	public function test_content_editorial_and_edicion_impresa_return_only_the_latest_post_in_each_category(): void {
		$editorialId = self::factory()->category->create( array( 'slug' => 'editorial' ) );
		$impresaId   = self::factory()->category->create( array( 'slug' => 'edicion-impresa' ) );

		self::factory()->post->create(
			array(
				'post_category' => array( $editorialId ),
				'post_date'     => '2020-01-01 00:00:00',
			)
		);
		$newestEditorial = self::factory()->post->create(
			array(
				'post_category' => array( $editorialId ),
				'post_date'     => '2024-01-01 00:00:00',
			)
		);
		$impresaPost     = self::factory()->post->create( array( 'post_category' => array( $impresaId ) ) );

		$content = ( new HomeContentProvider() )->content();

		self::assertNotNull( $content['editorial'] );
		self::assertSame( $newestEditorial, $content['editorial']->ID );
		self::assertNotNull( $content['edicionImpresa'] );
		self::assertSame( $impresaPost, $content['edicionImpresa']->ID );
	}

	public function test_content_does_not_include_draft_posts(): void {
		self::factory()->post->create( array( 'post_status' => 'draft' ) );

		$content = ( new HomeContentProvider() )->content();

		self::assertNull( $content['hero'] );
	}

	/**
	 * Las pruebas de integración de dnorte-theme no cargan dnorte-core.php como
	 * mu-plugin (ver tests/Integration/bootstrap.php) — Application::instance()
	 * nunca arranca en este proceso, así que esto ejercita de verdad la ruta de
	 * respaldo real que también cubre a un sitio con Analítica desactivada.
	 */
	public function test_most_read_falls_back_to_recent_posts_when_the_pageview_repository_is_unavailable(): void {
		self::factory()->post->create_many( 3 );

		$content = ( new HomeContentProvider() )->content();

		self::assertCount( 3, $content['mostRead'] );
	}
}
