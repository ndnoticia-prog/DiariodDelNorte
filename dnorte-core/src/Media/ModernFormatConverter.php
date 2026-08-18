<?php
/**
 * Hace que WordPress genere los tamaños intermedios de las imágenes subidas
 * (JPEG/PNG) en un formato moderno (WebP/AVIF) usando el filtro nativo
 * `image_editor_output_format` (WordPress 5.8+), sin depender de un servicio
 * externo. Comprueba en tiempo real si el GD del servidor soporta el formato
 * elegido antes de activarlo — degrada a no convertir si no hay soporte, nunca
 * fuerza un formato no disponible. Mismo criterio que ND Platform.
 *
 * @package DNorteCore\Media
 */

declare(strict_types=1);

namespace DNorteCore\Media;

use DNorteCore\Config\Config;

final class ModernFormatConverter {

	public function __construct( private readonly Config $config ) {
	}

	/**
	 * @param array<string, string> $formats
	 * @return array<string, string>
	 */
	public function filterOutputFormat( array $formats ): array {
		$targetMime = $this->preferredMime();

		if ( $targetMime === null ) {
			return $formats;
		}

		$formats['image/jpeg'] = $targetMime;
		$formats['image/png']  = $targetMime;

		return $formats;
	}

	private function preferredMime(): ?string {
		$format = $this->config->get( 'media.modern_format' );

		if ( $format === 'avif' ) {
			return $this->supportsAvif() ? 'image/avif' : ( $this->supportsWebp() ? 'image/webp' : null );
		}

		if ( $format === 'webp' ) {
			return $this->supportsWebp() ? 'image/webp' : null;
		}

		return null;
	}

	private function supportsAvif(): bool {
		return function_exists( 'imageavif' );
	}

	private function supportsWebp(): bool {
		return function_exists( 'imagewebp' );
	}
}
