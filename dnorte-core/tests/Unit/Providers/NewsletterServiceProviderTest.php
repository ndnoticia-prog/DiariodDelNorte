<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Providers;

use Brain\Monkey\Functions;
use DNorteCore\Container\Container;
use DNorteCore\Hooks\HookManager;
use DNorteCore\Newsletter\NewsletterAdminPage;
use DNorteCore\Newsletter\NewsletterController;
use DNorteCore\Providers\NewsletterServiceProvider;
use DNorteCore\Tests\Unit\TestCase;
use Mockery;

final class NewsletterServiceProviderTest extends TestCase {

	public function test_boot_wires_the_registration_hooks(): void {
		Functions\expect( 'add_filter' )
			->once()
			->with( 'dnorte_core/rest_controllers', Mockery::type( 'callable' ), 10, 1 );
		Functions\expect( 'add_filter' )
			->once()
			->with( 'dnorte_core/admin_pages', Mockery::type( 'callable' ), 10, 1 );

		$container = new Container();
		$hooks     = new HookManager();
		$container->instance( HookManager::class, $hooks );

		( new NewsletterServiceProvider( $container ) )->boot();
		$hooks->flush();

		$this->addToAssertionCount( 1 );
	}

	public function test_add_rest_controllers_appends_the_newsletter_controller(): void {
		$container = new Container();

		$result = ( new NewsletterServiceProvider( $container ) )->addRestControllers( array() );

		self::assertSame( array( NewsletterController::class ), $result );
	}

	public function test_add_admin_pages_appends_the_newsletter_admin_page(): void {
		$container = new Container();

		$result = ( new NewsletterServiceProvider( $container ) )->addAdminPages( array() );

		self::assertSame( array( NewsletterAdminPage::class ), $result );
	}
}
