<?php
/**
 * Configuración del módulo de publicidad propia. Cargado automáticamente bajo la
 * clave "ads" (ej. Config::get('ads.slots')).
 *
 * @package DNorteCore
 */

declare(strict_types=1);

return array(
	// Los cinco espacios pedidos explícitamente para Diario del Norte. La clave es
	// el slot_key que usan AdRepository/AdsServiceProvider/AdsAdminPage — cambiarla
	// aquí sin migrar los anuncios ya guardados los deja huérfanos, tratar como
	// estable una vez publicado.
	'slots'              => array(
		'cabecera'    => 'Cabecera (encima de la barra superior, en todo el sitio)',
		'inicio'      => 'Inicio (debajo del menú, en todo el sitio)',
		'top_noticia' => 'Top noticia (al iniciar la lectura del artículo)',
		'intermedio'  => 'Intermedio (después del tercer párrafo del artículo)',
		'final'       => 'Final (al terminar la lectura del artículo)',
	),
	// Tipos de contenido que cuentan como "artículo" para top_noticia/intermedio/
	// final — mismo criterio que search.post_types/analytics.tracked_post_types.
	'article_post_types' => array( 'post' ),
	// Párrafo del contenido después del cual se inserta el espacio "intermedio"
	// (ver Ads\ContentParagraphInjector). Si el artículo tiene menos párrafos que
	// esto, el anuncio simplemente no se inserta — nunca se fuerza al final.
	'mid_article_paragraph' => 3,
);
