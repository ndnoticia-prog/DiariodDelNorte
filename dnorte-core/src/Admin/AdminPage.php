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
	 * @param string|null $parentSlug Slug de la página padre (`add_submenu_page()`).
	 *                                 `null` (por defecto) la convierte en su PROPIA
	 *                                 entrada de nivel superior (`add_menu_page()`) —
	 *                                 corregido en v0.1.0-alpha.11: antes,
	 *                                 AdminMenuServiceProvider elegía la página de
	 *                                 menor `position` de TODA la plataforma (entre
	 *                                 módulos sin ninguna relación entre sí) como el
	 *                                 único nivel superior y anidaba cualquier otra
	 *                                 página nueva debajo — invisible mientras solo
	 *                                 existía un módulo con admin page (Turnos), pero
	 *                                 habría anidado "Analítica" bajo "Turnos" sin
	 *                                 ninguna relación real entre ambos. Ver "Fixed"
	 *                                 en CHANGELOG.md. Para un submenú propio de un
	 *                                 módulo (ej. "Turnos → Ajustes") pasar el slug
	 *                                 de la página de nivel superior de ese mismo
	 *                                 módulo, nunca inferirlo por posición.
	 * @param int $position Orden relativo entre páginas de nivel superior, o entre
	 *                       submenús que comparten el mismo $parentSlug — no influye
	 *                       en cuál se vuelve de nivel superior (eso ahora lo decide
	 *                       $parentSlug explícitamente, no la posición más baja).
	 */
	public function __construct(
		public readonly string $slug,
		public readonly string $pageTitle,
		public readonly string $menuTitle,
		public readonly string $capability,
		callable $render,
		public readonly int $position = 10,
		public readonly string $icon = 'dashicons-admin-generic',
		public readonly ?string $parentSlug = null
	) {
		$this->render = Closure::fromCallable( $render );
	}
}
