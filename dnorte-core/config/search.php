<?php
/**
 * Configuración del módulo de búsqueda interna. Cargado automáticamente bajo la
 * clave "search" (ej. Config::get('search.min_query_length')).
 *
 * @package DNorteCore
 */

declare(strict_types=1);

return array(
	// Tipos de contenido que la búsqueda cubre — solo artículos por defecto. Un
	// módulo futuro (ej. páginas institucionales) puede ampliar esta lista sin
	// tocar SearchQueryModifier/InternalSearchController.
	'post_types'           => array( 'post' ),
	// Búsquedas más cortas que esto se dejan con el comportamiento nativo de
	// WordPress (LIKE) en vez de MATCH AGAINST — MySQL ignora de todos modos
	// las palabras de 3 caracteres o menos en un índice FULLTEXT por defecto
	// (ft_min_word_len), así que una consulta más corta casi nunca encontraría
	// nada relevante.
	'min_query_length'     => 3,
	// Resultados máximos que devuelve GET /wp-json/dnorte/v1/search (pensado para
	// una caja de búsqueda con sugerencias en vivo, no para la página de
	// resultados completa, que pagina con the_posts_pagination() normalmente).
	'results_per_request'  => 8,
);
