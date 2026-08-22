<?php
/**
 * Configuración del módulo SEO. Cargado automáticamente bajo la clave "seo" (ej.
 * Config::get('seo.news_sitemap.window_hours')).
 *
 * @package DNorteCore
 */

declare(strict_types=1);

return array(
	'news_sitemap' => array(
		// Google News solo acepta artículos publicados en las últimas 48 horas
		// (requisito del propio formato, no configurable por el editor).
		'window_hours' => 48,
		// Código de idioma ISO 639 de <news:language> — ajustar si el sitio
		// publica en un idioma distinto al español.
		'language' => 'es',
	),
);
