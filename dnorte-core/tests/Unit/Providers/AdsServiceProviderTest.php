<?php
/**
 * renderCabecera()/renderInicio()/injectArticleAds() en sí (la resolución de
 * AdRepository vía el contenedor y su comportamiento real) no se cubren aquí a
 * propósito — ver el docblock de AdsServiceProvider: dependen en cadena de wpdb,
 * inexistente en este proceso de pruebas. Cubierto de punta a punta por
 * `tests/Integration/Ads/ArticleAdInjectionTest.php`.
 *
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Providers;

use Brain\Monkey\Functions;
use DNorteCore\Ads\AdsAdminPage;
use DNorteCore\Container\Container;
use DNorteCore\Hooks\HookManager;
use DNorteCore\Providers\AdsServiceProvider;
use DNorteCore\Tests\Unit\TestCase;
use Mockery;

final class AdsServiceProviderTest extends TestCase {

	public function test_boot_wires_the_sitewide_and_article_ad_hooks(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'dnorte_theme/before_topbar', Mockery::type( 'callable' ), 10, 1 );
		Functions\expect( 'add_action' )
			->once()
			->with( 'dnorte_theme/after_header', Mockery::type( 'callable' ), 10, 1 );
		Functions\expect( 'add_filter' )
			->once()
			->with( 'the_content', Mockery::type( 'callable' ), 20, 1 );
		Functions\expect( 'add_filter' )
			->once()
			->with( 'dnorte_core/admin_pages', Mockery::type( 'callable' ), 10, 1 );

		$container = new Container();
		$hooks     = new HookManager();
		$container->instance( HookManager::class, $hooks );

		( new AdsServiceProvider( $container ) )->boot();
		$hooks->flush();

		$this->addToAssertionCount( 1 );
	}

	public function test_add_admin_pages_appends_the_ads_admin_page(): void {
		$container = new Container();

		$result = ( new AdsServiceProvider( $container ) )->addAdminPages( array() );

		self::assertSame( array( AdsAdminPage::class ), $result );
	}
}
