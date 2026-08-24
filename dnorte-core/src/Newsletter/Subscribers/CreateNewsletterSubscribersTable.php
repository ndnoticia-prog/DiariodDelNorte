<?php
/**
 * @package DNorteCore\Newsletter\Subscribers
 */

declare(strict_types=1);

namespace DNorteCore\Newsletter\Subscribers;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Migrator\Contracts\Migration;

final class CreateNewsletterSubscribersTable implements Migration {

	public function name(): string {
		return 'create_newsletter_subscribers_table';
	}

	public function up( DatabaseManager $database ): void {
		$table = $database->table( 'newsletter_subscribers' );

		// UNIQUE en email: primera defensa contra duplicados (la comprobación real
		// vive en NewsletterSubscriberRepository::subscribe(), esto es solo un
		// respaldo a nivel de base de datos si dos peticiones llegan a la vez).
		$database->unprepared(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				email VARCHAR(190) NOT NULL,
				subscribed_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY email (email)
			)"
		);
	}

	public function down( DatabaseManager $database ): void {
		$database->unprepared( "DROP TABLE IF EXISTS {$database->table( 'newsletter_subscribers' )}" );
	}
}
