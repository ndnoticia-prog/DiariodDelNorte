<?php
/**
 * Configuración del módulo de multimedia. Cargado automáticamente bajo la clave
 * "media" (ej. Config::get('media.modern_format')).
 *
 * @package DNorteCore
 */

declare(strict_types=1);

return array(
	// 'webp' | 'avif' | null (desactiva la conversión de formato).
	// AVIF cae a WebP automáticamente si el servidor no lo soporta; WebP sin
	// soporte no cae a nada — nunca fuerza un formato no disponible.
	'modern_format' => 'webp',

	// Nombre del tamaño de imagen destacada (1200×675, ≥1200px de ancho requerido
	// por Google Discover). Referenciado como cadena literal desde otros módulos
	// (ej. Seo\Context\SeoContextResolver) para no acoplarlos a esta clase.
	'featured_image_size' => 'dnorte-featured',
);
