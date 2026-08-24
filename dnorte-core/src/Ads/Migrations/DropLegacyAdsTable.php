<?php
/**
 * Reemplaza el modelo de v0.1.0-alpha.12 (un único anuncio activo por espacio,
 * tabla `dnorte_ads`) por el de campañas de v0.1.0-alpha.13
 * (Ads\Migrations\CreateAdCampaignsTable): una campaña puede dirigirse a varios
 * espacios a la vez, con prioridad, segmentación por categoría y soporte nativo
 * de Google AdSense — pedido explícitamente tras ver el formulario real de
 * campañas del cliente.
 *
 * Migración nueva, no una reescritura de CreateAdsTable — esta plataforma nunca
 * reescribe una migración ya publicada (ver el docblock de Migrator\Migrator y
 * Installer\MigrationRegistry). Un sitio que instale desde cero corre
 * CreateAdsTable y la deja vacía un instante antes de que esta la elimine;
 * desperdicia una consulta, nunca un dato real (el modelo anterior nunca llegó a
 * producción).
 *
 * @package DNorteCore\Ads\Migrations
 */

declare(strict_types=1);

namespace DNorteCore\Ads\Migrations;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Migrator\Contracts\Migration;

final class DropLegacyAdsTable implements Migration {

	public function name(): string {
		return 'drop_legacy_ads_table';
	}

	public function up( DatabaseManager $database ): void {
		$database->unprepared( "DROP TABLE IF EXISTS {$database->table( 'ads' )}" );
	}

	public function down( DatabaseManager $database ): void {
		// Irreversible a propósito: recrear la tabla vieja sin sus datos originales
		// no devolvería nada útil. down() existe por el contrato Migration, no
		// porque haya un camino de vuelta real.
	}
}
