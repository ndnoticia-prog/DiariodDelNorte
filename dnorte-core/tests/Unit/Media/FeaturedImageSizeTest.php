<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Media;

use Brain\Monkey\Functions;
use DNorteCore\Config\Config;
use DNorteCore\Media\FeaturedImageSize;
use DNorteCore\Tests\Unit\TestCase;

final class FeaturedImageSizeTest extends TestCase {

	public function test_register_adds_the_configured_size_cropped_to_1200_by_675(): void {
		$config = new Config( array( 'media' => array( 'featured_image_size' => 'dnorte-featured' ) ) );

		Functions\expect( 'add_image_size' )
			->once()
			->with( 'dnorte-featured', 1200, 675, true );

		( new FeaturedImageSize( $config ) )->register();

		$this->addToAssertionCount( 1 );
	}

	public function test_name_returns_the_configured_size_name(): void {
		$config = new Config( array( 'media' => array( 'featured_image_size' => 'dnorte-featured' ) ) );

		self::assertSame( 'dnorte-featured', ( new FeaturedImageSize( $config ) )->name() );
	}

	public function test_register_does_nothing_when_no_size_name_is_configured(): void {
		$config = new Config( array( 'media' => array( 'featured_image_size' => '' ) ) );

		Functions\expect( 'add_image_size' )->never();

		( new FeaturedImageSize( $config ) )->register();

		$this->addToAssertionCount( 1 );
	}
}
