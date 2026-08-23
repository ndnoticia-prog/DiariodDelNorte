<?php
/**
 * De punta a punta: el filtro `the_content` real (registrado por
 * Providers\AdsServiceProvider durante el arranque normal de la aplicación, no uno
 * simulado aquí) inyectando los tres espacios de artículo sobre un WP_Post real
 * dentro de un bucle real (`go_to()` + `have_posts()`/`the_post()`) — el único modo
 * en que `in_the_loop()`/`is_main_query()` responden como en un sitio real.
 *
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Ads;

use DNorteCore\Ads\AdRepository;
use DNorteCore\Database\DatabaseManager;
use WP_UnitTestCase;

final class ArticleAdInjectionTest extends WP_UnitTestCase {

	public function test_the_content_filter_injects_the_three_article_slots_in_order(): void {
		global $wpdb;
		$repository = new AdRepository( new DatabaseManager( $wpdb ) );

		$repository->upsert( 'top_noticia', 'TOP-MARKER', true, null, null );
		$repository->upsert( 'intermedio', 'MID-MARKER', true, null, null );
		$repository->upsert( 'final', 'FINAL-MARKER', true, null, null );

		$postId = self::factory()->post->create(
			array( 'post_content' => "Uno.\n\nDos.\n\nTres.\n\nCuatro." )
		);

		$this->go_to( get_permalink( $postId ) );

		$rendered = '';

		while ( have_posts() ) {
			the_post();
			$rendered = apply_filters( 'the_content', get_the_content() );
		}

		self::assertStringContainsString( 'TOP-MARKER', $rendered );
		self::assertStringContainsString( 'MID-MARKER', $rendered );
		self::assertStringContainsString( 'FINAL-MARKER', $rendered );

		$topPos    = strpos( $rendered, 'TOP-MARKER' );
		$unoPos    = strpos( $rendered, 'Uno.' );
		$tresPos   = strpos( $rendered, 'Tres.' );
		$midPos    = strpos( $rendered, 'MID-MARKER' );
		$cuatroPos = strpos( $rendered, 'Cuatro.' );
		$finalPos  = strpos( $rendered, 'FINAL-MARKER' );

		self::assertNotFalse( $topPos );
		self::assertNotFalse( $unoPos );
		self::assertNotFalse( $tresPos );
		self::assertNotFalse( $midPos );
		self::assertNotFalse( $cuatroPos );
		self::assertNotFalse( $finalPos );

		self::assertLessThan( $unoPos, $topPos, 'TOP debe ir antes del primer párrafo.' );
		self::assertGreaterThan( $tresPos, $midPos, 'MID debe ir después del tercer párrafo.' );
		self::assertLessThan( $cuatroPos, $midPos, 'MID debe ir antes del cuarto párrafo.' );
		self::assertGreaterThan( $cuatroPos, $finalPos, 'FINAL debe ir después de todo el contenido.' );
	}

	public function test_the_content_filter_does_not_apply_outside_the_main_loop(): void {
		global $wpdb;
		( new AdRepository( new DatabaseManager( $wpdb ) ) )->upsert( 'top_noticia', 'TOP-MARKER', true, null, null );

		$postId = self::factory()->post->create( array( 'post_content' => 'Contenido simple.' ) );

		// Sin go_to()/the_post(): in_the_loop() e is_main_query() son falsos, así
		// que AdsServiceProvider::injectArticleAds() debe dejar el contenido igual.
		$rendered = apply_filters( 'the_content', get_post_field( 'post_content', $postId ) );

		self::assertStringNotContainsString( 'TOP-MARKER', $rendered );
	}

	public function test_a_disabled_ad_is_not_injected(): void {
		global $wpdb;
		( new AdRepository( new DatabaseManager( $wpdb ) ) )->upsert( 'top_noticia', 'TOP-MARKER', false, null, null );

		$postId = self::factory()->post->create( array( 'post_content' => 'Contenido simple.' ) );

		$this->go_to( get_permalink( $postId ) );

		$rendered = '';

		while ( have_posts() ) {
			the_post();
			$rendered = apply_filters( 'the_content', get_the_content() );
		}

		self::assertStringNotContainsString( 'TOP-MARKER', $rendered );
	}
}
