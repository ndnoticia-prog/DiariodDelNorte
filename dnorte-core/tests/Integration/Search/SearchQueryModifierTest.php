<?php
/**
 * Prueba de punta a punta contra artículos reales: confirma que el ranking por
 * relevancia (MATCH ... AGAINST) funciona de verdad contra MySQL/MariaDB, no solo
 * que el fragmento SQL se construye bien (eso ya lo cubre
 * BooleanModeTermBuilderTest a nivel unitario).
 *
 * Los artículos de fixture se crean en wpSetUpBeforeClass(), no dentro de cada
 * método de prueba — hallazgo real durante la primera corrida de esta prueba:
 * InnoDB no hace visibles las filas insertadas en la MISMA transacción sin
 * confirmar (commit) a una búsqueda MATCH ... AGAINST contra un índice FULLTEXT
 * (limitación documentada de InnoDB, no un bug de SearchQueryModifier), y
 * WP_UnitTestCase envuelve cada método de prueba en una transacción que nunca se
 * confirma (solo ROLLBACK, para aislar pruebas entre sí). wpSetUpBeforeClass()
 * corre antes de que esa transacción por-prueba se abra, así que sus INSERT sí
 * quedan confirmados y visibles para MATCH AGAINST — mismo tipo de interacción ya
 * documentada para DDL en "DDL y aislamiento transaccional" (docs/Architecture.md),
 * aquí en la dirección opuesta (DML invisible en vez de DDL forzando un commit).
 *
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Search;

use DNorteCore\Config\Config;
use DNorteCore\Database\DatabaseManager;
use DNorteCore\Search\BooleanModeTermBuilder;
use DNorteCore\Search\Fulltext\CreateSearchFulltextIndex;
use DNorteCore\Search\SearchQueryModifier;
use WP_Query;
use WP_UnitTestCase;

final class SearchQueryModifierTest extends WP_UnitTestCase {

	private static int $strongMatchPostId;

	public static function wpSetUpBeforeClass( \WP_UnitTest_Factory $factory ): void {
		global $wpdb;

		( new CreateSearchFulltextIndex() )->up( new DatabaseManager( $wpdb ) );

		$factory->post->create(
			array(
				'post_title'   => 'Noticias generales de la ciudad',
				'post_content' => 'El concejo aparece mencionado una sola vez en este artículo.',
			)
		);
		self::$strongMatchPostId = $factory->post->create(
			array(
				'post_title'   => 'Concejo municipal aprueba el presupuesto',
				'post_content' => 'El concejo municipal debatió el presupuesto del concejo durante horas.',
			)
		);
	}

	public function test_relevance_ranking_orders_the_stronger_textual_match_first(): void {
		$modifier = $this->modifier();

		add_filter( 'posts_search', array( $modifier, 'modifySearch' ), 10, 2 );
		add_filter( 'posts_orderby', array( $modifier, 'modifyOrderby' ), 10, 2 );

		$query = new WP_Query(
			array(
				's'              => 'concejo municipal',
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 10,
			)
		);

		remove_filter( 'posts_search', array( $modifier, 'modifySearch' ), 10 );
		remove_filter( 'posts_orderby', array( $modifier, 'modifyOrderby' ), 10 );

		$ids = wp_list_pluck( $query->posts, 'ID' );

		self::assertNotSame( array(), $ids );
		self::assertSame( self::$strongMatchPostId, $ids[0] );
	}

	public function test_a_query_shorter_than_the_configured_minimum_leaves_native_search_untouched(): void {
		$modifier = $this->modifier(); // min_query_length: 3 (ver modifier()).
		$query    = new WP_Query( array( 's' => 'el' ) );

		self::assertSame( 'ORIGINAL SEARCH', $modifier->modifySearch( 'ORIGINAL SEARCH', $query ) );
		self::assertSame( 'ORIGINAL ORDERBY', $modifier->modifyOrderby( 'ORIGINAL ORDERBY', $query ) );
	}

	public function test_a_non_search_query_leaves_native_search_and_orderby_untouched(): void {
		$modifier = $this->modifier();
		$query    = new WP_Query( array( 'post_type' => 'post' ) );

		self::assertSame( 'ORIGINAL SEARCH', $modifier->modifySearch( 'ORIGINAL SEARCH', $query ) );
		self::assertSame( 'ORIGINAL ORDERBY', $modifier->modifyOrderby( 'ORIGINAL ORDERBY', $query ) );
	}

	private function modifier(): SearchQueryModifier {
		global $wpdb;

		return new SearchQueryModifier(
			new DatabaseManager( $wpdb ),
			new Config( array( 'search' => array( 'min_query_length' => 3 ) ) ),
			new BooleanModeTermBuilder()
		);
	}
}
