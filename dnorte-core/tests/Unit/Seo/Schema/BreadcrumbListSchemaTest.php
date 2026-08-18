<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Seo\Schema;

use DNorteCore\Seo\Schema\BreadcrumbListSchema;
use DNorteCore\Tests\Unit\TestCase;

final class BreadcrumbListSchemaTest extends TestCase {

	public function test_build_numbers_positions_starting_at_one(): void {
		$node = ( new BreadcrumbListSchema() )->build(
			array(
				array(
					'name' => 'Inicio',
					'url'  => 'https://example.test/',
				),
				array(
					'name' => 'Nacional',
					'url'  => 'https://example.test/nacional/',
				),
			)
		);

		self::assertSame( 'BreadcrumbList', $node['@type'] );
		self::assertSame( 1, $node['itemListElement'][0]['position'] );
		self::assertSame( 2, $node['itemListElement'][1]['position'] );
		self::assertSame( 'Nacional', $node['itemListElement'][1]['name'] );
	}

	public function test_build_with_a_single_item_still_produces_a_valid_list(): void {
		$node = ( new BreadcrumbListSchema() )->build(
			array(
				array(
					'name' => 'Inicio',
					'url'  => 'https://example.test/',
				),
			)
		);

		self::assertCount( 1, $node['itemListElement'] );
	}
}
