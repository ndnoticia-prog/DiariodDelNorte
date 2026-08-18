<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Seo\Schema;

use Brain\Monkey\Functions;
use DNorteCore\Seo\Schema\SchemaOutput;
use DNorteCore\Tests\Unit\TestCase;

final class SchemaOutputTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_json_encode' )->alias(
			static fn ( mixed $value, int $flags = 0 ): string|false => json_encode( $value, $flags )
		);
	}

	public function test_to_script_returns_empty_string_when_there_are_no_nodes(): void {
		self::assertSame( '', ( new SchemaOutput() )->toScript( array() ) );
	}

	public function test_to_script_wraps_all_nodes_in_a_single_graph(): void {
		$script = ( new SchemaOutput() )->toScript(
			array(
				array(
					'@type' => 'Organization',
					'name'  => 'Diario del Norte',
				),
				array(
					'@type' => 'WebSite',
					'name'  => 'Diario del Norte',
				),
			)
		);

		self::assertStringStartsWith( '<script type="application/ld+json">', $script );
		self::assertStringEndsWith( '</script>', $script );

		$json = trim( str_replace( array( '<script type="application/ld+json">', '</script>' ), '', $script ) );
		$data = json_decode( $json, true );

		self::assertSame( 'https://schema.org', $data['@context'] );
		self::assertCount( 2, $data['@graph'] );
	}

	public function test_to_script_escapes_closing_script_tags_inside_node_values(): void {
		$script = ( new SchemaOutput() )->toScript(
			array(
				array(
					'@type'    => 'NewsArticle',
					'headline' => 'Título con </script><script>alert(1)</script> dentro',
				),
			)
		);

		self::assertStringNotContainsString( '</script><script>alert(1)</script>', $script );
	}
}
