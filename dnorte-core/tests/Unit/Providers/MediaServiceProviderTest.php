<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Providers;

use Brain\Monkey\Functions;
use DNorteCore\Config\Config;
use DNorteCore\Container\Container;
use DNorteCore\Hooks\HookManager;
use DNorteCore\Providers\MediaServiceProvider;
use DNorteCore\Tests\Unit\TestCase;
use Mockery;

final class MediaServiceProviderTest extends TestCase {

	public function test_boot_wires_the_output_format_filter_and_the_featured_size_action(): void {
		Functions\expect( 'add_filter' )
			->once()
			->with( 'image_editor_output_format', Mockery::type( 'callable' ), 10, 1 );
		Functions\expect( 'add_action' )
			->once()
			->with( 'after_setup_theme', Mockery::type( 'callable' ), 10, 1 );

		$container = new Container();
		$hooks     = new HookManager();
		$container->instance( HookManager::class, $hooks );
		$container->instance( Config::class, new Config() );

		( new MediaServiceProvider( $container ) )->boot();
		$hooks->flush();

		$this->addToAssertionCount( 1 );
	}

	public function test_filter_output_format_delegates_to_modern_format_converter(): void {
		Functions\expect( 'function_exists' )->with( 'imagewebp' )->andReturn( true );

		$container = new Container();
		$container->instance( Config::class, new Config( array( 'media' => array( 'modern_format' => 'webp' ) ) ) );

		$result = ( new MediaServiceProvider( $container ) )->filterOutputFormat( array( 'image/jpeg' => 'image/jpeg' ) );

		self::assertSame( 'image/webp', $result['image/jpeg'] );
	}

	public function test_register_featured_image_size_delegates_to_featured_image_size(): void {
		Functions\expect( 'add_image_size' )
			->once()
			->with( 'dnorte-featured', 1200, 675, true );

		$container = new Container();
		$container->instance( Config::class, new Config( array( 'media' => array( 'featured_image_size' => 'dnorte-featured' ) ) ) );

		( new MediaServiceProvider( $container ) )->registerFeaturedImageSize();

		$this->addToAssertionCount( 1 );
	}
}
