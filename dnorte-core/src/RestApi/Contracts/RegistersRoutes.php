<?php
/**
 * @package DNorteCore\RestApi\Contracts
 */

declare(strict_types=1);

namespace DNorteCore\RestApi\Contracts;

use DNorteCore\Routing\Router;

interface RegistersRoutes {

	public function registerRoutes( Router $router ): void;
}
