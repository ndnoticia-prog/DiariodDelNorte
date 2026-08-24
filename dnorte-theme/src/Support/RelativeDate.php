<?php
/**
 * "Hace 2 horas" en vez de una fecha absoluta — usa human_time_diff() de
 * WordPress core (ya localizado si el sitio tiene el locale es_CO/es_ES
 * instalado, sin reinventar el cálculo ni las cadenas "hace X
 * minutos/horas/días"). Reutilizado por el hero y las tarjetas de noticia
 * (La Guajira, Judiciales, Más noticias) — Opinión y Edición Impresa siguen
 * mostrando una fecha absoluta a propósito (ver sus propias plantillas).
 *
 * @package DNorteTheme\Support
 */

declare(strict_types=1);

namespace DNorteTheme\Support;

use WP_Post;

final class RelativeDate {

	public static function forPost( WP_Post $post ): string {
		$publishedAt = (int) get_post_time( 'U', true, $post );
		// time() ya es un timestamp Unix en UTC — current_time('timestamp', true)
		// devolvería exactamente lo mismo, solo que WPCS lo marca como error por el
		// argumento 'timestamp' (desaconsejado incluso con $gmt=true).
		$now = time();

		return sprintf(
			/* translators: %s: tiempo transcurrido, ej. "2 horas". */
			__( 'Hace %s', 'dnorte-theme' ),
			human_time_diff( $publishedAt, $now )
		);
	}
}
