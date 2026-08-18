<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Config;

use DNorteCore\Config\Config;
use DNorteCore\Tests\Unit\TestCase;

final class ConfigTest extends TestCase {

	public function test_get_returns_default_for_missing_key(): void {
		$config = new Config();

		self::assertNull( $config->get( 'missing.key' ) );
		self::assertSame( 'fallback', $config->get( 'missing.key', 'fallback' ) );
	}

	public function test_get_resolves_dot_notation_from_constructor_items(): void {
		$config = new Config(
			array(
				'app' => array(
					'name'   => 'DNorte Core',
					'nested' => array( 'deep' => 'value' ),
				),
			)
		);

		self::assertSame( 'DNorte Core', $config->get( 'app.name' ) );
		self::assertSame( 'value', $config->get( 'app.nested.deep' ) );
	}

	public function test_get_returns_default_when_a_segment_is_not_an_array(): void {
		$config = new Config( array( 'app' => array( 'name' => 'DNorte Core' ) ) );

		self::assertSame( 'fallback', $config->get( 'app.name.deeper', 'fallback' ) );
	}

	public function test_set_writes_nested_keys_creating_intermediate_arrays(): void {
		$config = new Config();

		$config->set( 'app.nested.deep', 'value' );

		self::assertSame( 'value', $config->get( 'app.nested.deep' ) );
		self::assertIsArray( $config->get( 'app' ) );
	}

	public function test_set_overwrites_a_scalar_with_a_nested_array_when_needed(): void {
		$config = new Config( array( 'app' => 'not-an-array' ) );

		$config->set( 'app.name', 'DNorte Core' );

		self::assertSame( 'DNorte Core', $config->get( 'app.name' ) );
	}

	public function test_has_distinguishes_missing_from_present_including_null_values(): void {
		$config = new Config( array( 'app' => array( 'flag' => null ) ) );

		self::assertTrue( $config->has( 'app.flag' ) );
		self::assertFalse( $config->has( 'app.missing' ) );
	}

	public function test_load_directory_indexes_each_php_file_by_its_basename(): void {
		$directory = sys_get_temp_dir() . '/dnorte-config-test-' . uniqid( '', true );
		mkdir( $directory );
		file_put_contents( $directory . '/app.php', "<?php\nreturn ['name' => 'DNorte Core'];\n" );
		file_put_contents( $directory . '/media.php', "<?php\nreturn ['cdn' => 'https://cdn.example.test'];\n" );

		try {
			$config = new Config();
			$config->loadDirectory( $directory );

			self::assertSame( 'DNorte Core', $config->get( 'app.name' ) );
			self::assertSame( 'https://cdn.example.test', $config->get( 'media.cdn' ) );
		} finally {
			unlink( $directory . '/app.php' );
			unlink( $directory . '/media.php' );
			rmdir( $directory );
		}
	}

	public function test_load_directory_is_a_no_op_for_a_directory_without_php_files(): void {
		$directory = sys_get_temp_dir() . '/dnorte-config-empty-' . uniqid( '', true );
		mkdir( $directory );

		try {
			$config = new Config( array( 'app' => array( 'name' => 'DNorte Core' ) ) );
			$config->loadDirectory( $directory );

			self::assertSame( 'DNorte Core', $config->get( 'app.name' ) );
		} finally {
			rmdir( $directory );
		}
	}
}
