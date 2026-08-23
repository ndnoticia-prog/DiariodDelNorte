<?php
/**
 * @package DNorteCore\Ads
 */

declare(strict_types=1);

namespace DNorteCore\Ads;

use DNorteCore\Database\DatabaseManager;

final class AdRepository {

	public function __construct( private readonly DatabaseManager $database ) {
	}

	public function forSlot( string $slotKey ): ?Ad {
		$table = $this->database->table( 'ads' );

		$row = $this->database->selectOne(
			"SELECT * FROM {$table} WHERE slot_key = %s",
			array( $slotKey )
		);

		return $row !== null ? $this->hydrate( $row ) : null;
	}

	/**
	 * Crea o reemplaza el anuncio del espacio — un único anuncio activo por
	 * slot_key en v1 (ver el docblock de Migrations\CreateAdsTable).
	 */
	public function upsert( string $slotKey, string $html, bool $enabled, ?string $startsAt, ?string $endsAt ): void {
		$table    = $this->database->table( 'ads' );
		$existing = $this->forSlot( $slotKey );

		$data = array(
			'slot_key'   => $slotKey,
			'html'       => $html,
			'enabled'    => $enabled ? 1 : 0,
			'starts_at'  => $startsAt,
			'ends_at'    => $endsAt,
			'updated_at' => gmdate( 'Y-m-d H:i:s' ),
		);

		if ( $existing !== null ) {
			$this->database->update( $table, $data, array( 'id' => $existing->id ) );

			return;
		}

		$this->database->insert( $table, $data );
	}

	public function clear( string $slotKey ): void {
		$this->database->delete( $this->database->table( 'ads' ), array( 'slot_key' => $slotKey ) );
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function hydrate( array $row ): Ad {
		return new Ad(
			(int) $row['id'],
			(string) $row['slot_key'],
			(string) $row['html'],
			(int) $row['enabled'] === 1,
			$row['starts_at'] !== null ? (string) $row['starts_at'] : null,
			$row['ends_at'] !== null ? (string) $row['ends_at'] : null
		);
	}
}
