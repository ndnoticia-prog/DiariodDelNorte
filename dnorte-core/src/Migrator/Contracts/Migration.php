<?php
/**
 * @package DNorteCore\Migrator\Contracts
 */

declare(strict_types=1);

namespace DNorteCore\Migrator\Contracts;

use DNorteCore\Database\DatabaseManager;

interface Migration {

	/**
	 * Identificador único y estable de la migración (nunca reutilizar tras publicarla).
	 */
	public function name(): string;

	public function up( DatabaseManager $database ): void;

	public function down( DatabaseManager $database ): void;
}
