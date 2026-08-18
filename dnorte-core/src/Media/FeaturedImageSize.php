<?php
/**
 * Registra el tamaño de imagen destacada que exige Google Discover (imagen ≥1200px
 * de ancho, relación 16:9). El nombre del tamaño se referencia como cadena literal
 * desde otros módulos (ej. Seo\Context\SeoContextResolver), nunca como dependencia
 * de clase — así ningún otro módulo se acopla a éste. Mismo patrón que
 * `nd-discover`/`nd-theme` en ND Platform.
 *
 * @package DNorteCore\Media
 */

declare(strict_types=1);

namespace DNorteCore\Media;

use DNorteCore\Config\Config;

final class FeaturedImageSize {

	public function __construct( private readonly Config $config ) {
	}

	public function register(): void {
		$name = $this->name();

		if ( $name === '' ) {
			return;
		}

		add_image_size( $name, 1200, 675, true );
	}

	public function name(): string {
		$name = $this->config->get( 'media.featured_image_size', '' );

		return is_string( $name ) ? $name : '';
	}
}
