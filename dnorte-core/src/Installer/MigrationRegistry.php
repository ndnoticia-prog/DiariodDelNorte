<?php
/**
 * Lista central de todas las migraciones de la plataforma. Existe porque
 * register_activation_hook() debe ejecutarse en tiempo de carga del archivo
 * principal del plugin (ver dnorte-core.php) — antes de que Application::boot()
 * arranque en after_setup_theme y pueda resolver providers dinámicamente. Con un
 * único plugin (no un monorepo de paquetes independientes como ND Platform), una
 * lista explícita aquí es más simple que un sistema de filtros para algo que solo
 * dnorte-core necesita declarar.
 *
 * @package DNorteCore\Installer
 */

declare(strict_types=1);

namespace DNorteCore\Installer;

use DNorteCore\Migrator\Contracts\Migration;
use DNorteCore\Search\Fulltext\CreateSearchFulltextIndex;
use DNorteCore\Workflow\Notes\CreateEditorialNotesTable;
use DNorteCore\Workflow\Shifts\CreateShiftsTable;

final class MigrationRegistry {

	/**
	 * @return list<Migration>
	 */
	public static function all(): array {
		return array(
			new CreateEditorialNotesTable(),
			new CreateShiftsTable(),
			new CreateSearchFulltextIndex(),
		);
	}
}
