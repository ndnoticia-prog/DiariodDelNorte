<?php
/**
 * @package DNorteCore\Newsletter\Subscribers
 */

declare(strict_types=1);

namespace DNorteCore\Newsletter\Subscribers;

use DNorteCore\Database\DatabaseManager;

final class NewsletterSubscriberRepository {

	public function __construct( private readonly DatabaseManager $database ) {
	}

	/**
	 * Silenciosamente no hace nada si el correo ya estaba suscrito — un
	 * reenvío del mismo formulario (doble clic, recarga) nunca debe fallar ni
	 * duplicar la fila; UNIQUE KEY email en la tabla es el respaldo a nivel de
	 * base de datos para la misma garantía.
	 *
	 * @return bool true si se dio de alta ahora, false si ya estaba suscrito.
	 */
	public function subscribe( string $email ): bool {
		if ( $this->find( $email ) !== null ) {
			return false;
		}

		$this->database->insert(
			$this->database->table( 'newsletter_subscribers' ),
			array(
				'email'         => $email,
				'subscribed_at' => gmdate( 'Y-m-d H:i:s' ),
			)
		);

		return true;
	}

	/**
	 * @return array{email: string, subscribed_at: string}|null
	 */
	public function find( string $email ): ?array {
		$table = $this->database->table( 'newsletter_subscribers' );

		$row = $this->database->selectOne( "SELECT email, subscribed_at FROM {$table} WHERE email = %s", array( $email ) );

		return $row !== null ? array(
			'email'         => (string) $row['email'],
			'subscribed_at' => (string) $row['subscribed_at'],
		) : null;
	}

	public function count(): int {
		$table = $this->database->table( 'newsletter_subscribers' );
		$row   = $this->database->selectOne( "SELECT COUNT(*) as total FROM {$table}" );

		return $row !== null ? (int) $row['total'] : 0;
	}

	/**
	 * @return list<array{email: string, subscribed_at: string}>
	 */
	public function latest( int $limit = 200 ): array {
		$table = $this->database->table( 'newsletter_subscribers' );

		$rows = $this->database->select(
			"SELECT email, subscribed_at FROM {$table} ORDER BY subscribed_at DESC LIMIT %d",
			array( $limit )
		);

		return array_map(
			static fn ( array $row ): array => array(
				'email'         => (string) $row['email'],
				'subscribed_at' => (string) $row['subscribed_at'],
			),
			$rows
		);
	}
}
