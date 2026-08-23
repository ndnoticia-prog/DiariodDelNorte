<?php
/**
 * Configuración del módulo de analítica propia. Cargado automáticamente bajo la
 * clave "analytics" (ej. Config::get('analytics.retention_days')).
 *
 * @package DNorteCore
 */

declare(strict_types=1);

return array(
	// Tipos de contenido cuyas visitas se registran — solo artículos por defecto.
	'tracked_post_types'        => array( 'post' ),
	// Ventana del ranking de "artículos más vistos" del panel de administración.
	'top_articles_window_days'  => 7,
	// Filas de dnorte_pageviews más antiguas que esto se purgan a diario (WP-Cron,
	// ver Analytics\PageviewPurger) — evita que la tabla crezca sin límite. No es
	// un requisito de privacidad estricto (las filas no guardan IP ni ningún dato
	// personal, solo post_id/host del referente/fecha), es higiene de datos.
	'retention_days'            => 90,
);
