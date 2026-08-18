<?php
/**
 * Único punto del plugin que llama a register_rest_route(). Se usa siempre dentro
 * del hook `rest_api_init` (ver RestApiServiceProvider).
 *
 * @package DNorteCore\Routing
 */

declare(strict_types=1);

namespace DNorteCore\Routing;

final class Router {

	/**
	 * @param non-falsy-string $namespace
	 * @param non-falsy-string $route
	 * @param array<string, mixed> $args
	 */
	public function register( string $namespace, string $route, array $args ): void {
		register_rest_route( $namespace, $route, $args );
	}
}
