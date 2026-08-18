<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Routing;

use Brain\Monkey\Functions;
use DNorteCore\Routing\Router;
use DNorteCore\Tests\Unit\TestCase;

final class RouterTest extends TestCase {

	public function test_register_forwards_to_register_rest_route(): void {
		Functions\expect( 'register_rest_route' )
			->once()
			->with( 'dnorte/v1', '/system/status', array( 'methods' => 'GET' ) );

		( new Router() )->register( 'dnorte/v1', '/system/status', array( 'methods' => 'GET' ) );

		$this->addToAssertionCount( 1 );
	}
}
