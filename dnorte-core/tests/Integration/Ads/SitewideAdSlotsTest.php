<?php
/**
 * Cabecera/Inicio no dependen del bucle de WordPress (a diferencia de los tres
 * espacios de artículo) — se prueban invocando directamente los callbacks que
 * AdsServiceProvider engancha a los hooks propios de dnorte-theme
 * (`dnorte_theme/before_topbar`/`after_header`), usando el contenedor real ya
 * arrancado por Application (necesario para que AdRepository resuelva wpdb).
 * El caso "sin anuncio" ya lo cubre AdSlotRendererTest a nivel unitario.
 *
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Ads;

use DNorteCore\Ads\AdRepository;
use DNorteCore\Application;
use DNorteCore\Database\DatabaseManager;
use DNorteCore\Providers\AdsServiceProvider;
use WP_UnitTestCase;

final class SitewideAdSlotsTest extends WP_UnitTestCase {

	public function test_render_cabecera_outputs_the_active_ad_for_that_slot(): void {
		global $wpdb;
		( new AdRepository( new DatabaseManager( $wpdb ) ) )->upsert( 'cabecera', 'CABECERA-MARKER', true, null, null );

		$provider = new AdsServiceProvider( Application::instance()->container() );

		ob_start();
		$provider->renderCabecera();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'CABECERA-MARKER', $output );
		self::assertStringContainsString( 'dnorte-ad--cabecera', $output );
	}

	public function test_render_inicio_outputs_the_active_ad_for_that_slot(): void {
		global $wpdb;
		( new AdRepository( new DatabaseManager( $wpdb ) ) )->upsert( 'inicio', 'INICIO-MARKER', true, null, null );

		$provider = new AdsServiceProvider( Application::instance()->container() );

		ob_start();
		$provider->renderInicio();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'INICIO-MARKER', $output );
		self::assertStringContainsString( 'dnorte-ad--inicio', $output );
	}
}
