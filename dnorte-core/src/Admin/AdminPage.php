<?php
/**
 * Value object de una página de administración. Mismo patrón que el registro de
 * ServiceProviders/rutas REST: un filtro + un contrato + este value object,
 * centralizados por AdminMenuServiceProvider — cualquier módulo se suma sin que
 * dnorte-core tenga que conocer su existencia.
 *
 * @package DNorteCore\Admin
 */

declare(strict_types=1);

namespace DNorteCore\Admin;

use Closure;

final class AdminPage {

	public readonly Closure $render;

	/**
	 * @param int $position Determina el orden entre páginas y cuál se usa como
	 *                       entrada de nivel superior del menú (la de menor
	 *                       posición) — mismo criterio que WooCommerce/Yoast SEO,
	 *                       evita un submenú "índice" separado y huérfano.
	 */
	public function __construct(
		public readonly string $slug,
		public readonly string $pageTitle,
		public readonly string $menuTitle,
		public readonly string $capability,
		callable $render,
		public readonly int $position = 10,
		public readonly string $icon = 'dashicons-admin-generic'
	) {
		$this->render = Closure::fromCallable( $render );
	}
}
