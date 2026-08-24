<?php
/**
 * Cabecera/Inicio no dependen del bucle de WordPress (a diferencia de los tres
 * espacios de artículo) — se prueban invocando directamente los callbacks que
 * AdsServiceProvider engancha a los hooks propios de dnorte-theme
 * (`dnorte_theme/before_topbar`/`after_header`), usando el contenedor real ya
 * arrancado por Application (necesario para que CampaignRepository resuelva
 * wpdb). El caso "sin campaña" ya lo cubre AdSlotRendererTest a nivel unitario.
 *
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Ads;

use DNorteCore\Ads\Campaign;
use DNorteCore\Ads\CampaignRepository;
use DNorteCore\Application;
use DNorteCore\Database\DatabaseManager;
use DNorteCore\Providers\AdsServiceProvider;
use WP_UnitTestCase;

final class SitewideAdSlotsTest extends WP_UnitTestCase {

	public function test_render_cabecera_outputs_the_active_campaign_for_that_slot(): void {
		global $wpdb;
		( new CampaignRepository( new DatabaseManager( $wpdb ) ) )->save(
			new Campaign( 0, 'Cabecera', 'Anunciante', Campaign::TYPE_HTML, true, 0, array( 'cabecera' ), array(), null, null, 'CABECERA-MARKER', '', '' )
		);

		$provider = new AdsServiceProvider( Application::instance()->container() );

		ob_start();
		$provider->renderCabecera();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'CABECERA-MARKER', $output );
		self::assertStringContainsString( 'dnorte-ad--cabecera', $output );
	}

	public function test_render_inicio_outputs_the_active_campaign_for_that_slot(): void {
		global $wpdb;
		( new CampaignRepository( new DatabaseManager( $wpdb ) ) )->save(
			new Campaign( 0, 'Inicio', 'Anunciante', Campaign::TYPE_HTML, true, 0, array( 'inicio' ), array(), null, null, 'INICIO-MARKER', '', '' )
		);

		$provider = new AdsServiceProvider( Application::instance()->container() );

		ob_start();
		$provider->renderInicio();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'INICIO-MARKER', $output );
		self::assertStringContainsString( 'dnorte-ad--inicio', $output );
	}

	/**
	 * Una campaña puede dirigirse a Cabecera E Inicio a la vez — comportamiento
	 * nuevo en v0.1.0-alpha.13, imposible en el modelo de un anuncio por espacio
	 * de alpha.12.
	 */
	public function test_a_single_campaign_can_target_both_sitewide_slots_at_once(): void {
		global $wpdb;
		( new CampaignRepository( new DatabaseManager( $wpdb ) ) )->save(
			new Campaign( 0, 'Multi-zona', 'Anunciante', Campaign::TYPE_HTML, true, 0, array( 'cabecera', 'inicio' ), array(), null, null, 'MULTI-MARKER', '', '' )
		);

		$provider = new AdsServiceProvider( Application::instance()->container() );

		ob_start();
		$provider->renderCabecera();
		$cabecera = (string) ob_get_clean();

		ob_start();
		$provider->renderInicio();
		$inicio = (string) ob_get_clean();

		self::assertStringContainsString( 'MULTI-MARKER', $cabecera );
		self::assertStringContainsString( 'MULTI-MARKER', $inicio );
	}

	public function test_enqueue_adsense_loader_enqueues_the_script_when_an_active_adsense_campaign_exists(): void {
		global $wpdb;
		( new CampaignRepository( new DatabaseManager( $wpdb ) ) )->save(
			new Campaign( 0, 'AdSense', 'Google', Campaign::TYPE_ADSENSE, true, 0, array( 'cabecera' ), array(), null, null, '', 'ca-pub-1112223334', '5556667778' )
		);

		wp_dequeue_script( 'dnorte-adsense' );
		wp_deregister_script( 'dnorte-adsense' );

		$provider = new AdsServiceProvider( Application::instance()->container() );
		$provider->enqueueAdSenseLoader();

		self::assertTrue( wp_script_is( 'dnorte-adsense', 'enqueued' ) );

		global $wp_scripts;
		$registered = $wp_scripts->registered['dnorte-adsense'];
		self::assertStringContainsString( 'client=ca-pub-1112223334', $registered->src );
	}
}
