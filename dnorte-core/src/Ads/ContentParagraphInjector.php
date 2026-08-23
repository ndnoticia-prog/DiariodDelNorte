<?php
/**
 * Inserta un fragmento HTML después del N-ésimo `</p>` del contenido de un
 * artículo — la pieza que hace posible el espacio "intermedio" (después del
 * tercer párrafo). Pieza pura (ninguna dependencia de WordPress), separada a
 * propósito de Providers\AdsServiceProvider para poder probarla con PHPUnit sin
 * un WP_Post/`the_content` real — mismo criterio que
 * Search\BooleanModeTermBuilder.
 *
 * @package DNorteCore\Ads
 */

declare(strict_types=1);

namespace DNorteCore\Ads;

final class ContentParagraphInjector {

	/**
	 * Si el contenido tiene menos párrafos que $paragraphNumber, el fragmento
	 * simplemente no se inserta — nunca se fuerza a aparecer en otra parte (ej. al
	 * final), para no sorprender con una posición distinta a la configurada.
	 */
	public function insertAfterParagraph( string $content, string $insertion, int $paragraphNumber ): string {
		if ( $insertion === '' || $paragraphNumber < 1 ) {
			return $content;
		}

		$seen = 0;

		$result = preg_replace_callback(
			'/<\/p>/i',
			static function ( array $matches ) use ( $insertion, $paragraphNumber, &$seen ): string {
				++$seen;

				return $seen === $paragraphNumber ? $matches[0] . $insertion : $matches[0];
			},
			$content
		);

		return $result ?? $content;
	}
}
