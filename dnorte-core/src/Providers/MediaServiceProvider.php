<?php
/**
 * @package DNorteCore\Providers
 */

declare(strict_types=1);

namespace DNorteCore\Providers;

use DNorteCore\Config\Config;
use DNorteCore\Hooks\HookManager;
use DNorteCore\Media\FeaturedImageSize;
use DNorteCore\Media\ModernFormatConverter;

final class MediaServiceProvider extends ServiceProvider {

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->get( HookManager::class );

		$hooks->addFilter( 'image_editor_output_format', $this->filterOutputFormat( ... ), 10, 1 );
		$hooks->addAction( 'after_setup_theme', $this->registerFeaturedImageSize( ... ), 10 );
	}

	/**
	 * @param array<string, string> $formats
	 * @return array<string, string>
	 */
	public function filterOutputFormat( array $formats ): array {
		/** @var Config $config */
		$config = $this->container->get( Config::class );

		return ( new ModernFormatConverter( $config ) )->filterOutputFormat( $formats );
	}

	public function registerFeaturedImageSize(): void {
		/** @var Config $config */
		$config = $this->container->get( Config::class );

		( new FeaturedImageSize( $config ) )->register();
	}
}
