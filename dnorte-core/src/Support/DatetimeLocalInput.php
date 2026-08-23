<?php
/**
 * Convierte el valor de un `<input type="datetime-local">` ("YYYY-MM-DDTHH:MM")
 * al formato DATETIME de MySQL. Extraído en v0.1.0-alpha.12 desde
 * Workflow\Shifts\ShiftsAdminPage (que tenía su propia copia privada del mismo
 * método) al necesitarlo también Ads\AdsAdminPage — segundo uso real, momento
 * razonable para dejar de duplicarlo.
 *
 * @package DNorteCore\Support
 */

declare(strict_types=1);

namespace DNorteCore\Support;

final class DatetimeLocalInput {

	public static function toMysqlDatetime( string $value ): ?string {
		if ( $value === '' ) {
			return null;
		}

		$normalized = str_replace( 'T', ' ', $value );
		$timestamp  = strtotime( $normalized );

		return $timestamp !== false ? gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
	}
}
