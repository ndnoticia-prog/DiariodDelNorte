<?php
/**
 * @package DNorteCore\Admin\Contracts
 */

declare(strict_types=1);

namespace DNorteCore\Admin\Contracts;

use DNorteCore\Admin\AdminPage;

interface RegistersAdminPages {

	/**
	 * @return list<AdminPage>
	 */
	public function adminPages(): array;
}
