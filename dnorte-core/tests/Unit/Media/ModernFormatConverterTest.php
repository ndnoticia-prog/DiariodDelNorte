<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Media;

use Brain\Monkey\Functions;
use DNorteCore\Config\Config;
use DNorteCore\Media\ModernFormatConverter;
use DNorteCore\Tests\Unit\TestCase;

final class ModernFormatConverterTest extends TestCase {

	public function test_converts_to_webp_when_configured_and_supported(): void {
		$config = new Config( array( 'media' => array( 'modern_format' => 'webp' ) ) );
		Functions\expect( 'function_exists' )->with( 'imagewebp' )->andReturn( true );

		$result = ( new ModernFormatConverter( $config ) )->filterOutputFormat( array( 'image/jpeg' => 'image/jpeg' ) );

		self::assertSame( 'image/webp', $result['image/jpeg'] );
		self::assertSame( 'image/webp', $result['image/png'] );
	}

	public function test_leaves_formats_untouched_when_webp_unsupported(): void {
		$config = new Config( array( 'media' => array( 'modern_format' => 'webp' ) ) );
		Functions\expect( 'function_exists' )->with( 'imagewebp' )->andReturn( false );

		$result = ( new ModernFormatConverter( $config ) )->filterOutputFormat( array( 'image/jpeg' => 'image/jpeg' ) );

		self::assertSame( array( 'image/jpeg' => 'image/jpeg' ), $result );
	}

	public function test_avif_degrades_to_webp_when_avif_unsupported(): void {
		$config = new Config( array( 'media' => array( 'modern_format' => 'avif' ) ) );
		Functions\expect( 'function_exists' )->andReturnUsing(
			static fn ( string $function ): bool => $function === 'imagewebp'
		);

		$result = ( new ModernFormatConverter( $config ) )->filterOutputFormat( array( 'image/jpeg' => 'image/jpeg' ) );

		self::assertSame( 'image/webp', $result['image/jpeg'] );
	}

	public function test_avif_used_when_supported(): void {
		$config = new Config( array( 'media' => array( 'modern_format' => 'avif' ) ) );
		Functions\expect( 'function_exists' )->with( 'imageavif' )->andReturn( true );

		$result = ( new ModernFormatConverter( $config ) )->filterOutputFormat( array( 'image/jpeg' => 'image/jpeg' ) );

		self::assertSame( 'image/avif', $result['image/jpeg'] );
	}

	public function test_disabled_when_config_is_null(): void {
		$config = new Config( array( 'media' => array( 'modern_format' => null ) ) );

		$result = ( new ModernFormatConverter( $config ) )->filterOutputFormat( array( 'image/jpeg' => 'image/jpeg' ) );

		self::assertSame( array( 'image/jpeg' => 'image/jpeg' ), $result );
	}
}
