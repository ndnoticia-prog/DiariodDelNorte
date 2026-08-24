<?php
/**
 * Bitácora de la pestaña "Historial" del panel de Publicidad — quién hizo qué a
 * cada campaña y cuándo (crear/editar/activar/desactivar/borrar/subir evidencia).
 * `campaign_name` se guarda como copia (ver el docblock de la migración): una
 * campaña borrada sigue siendo legible en su propio historial.
 *
 * @package DNorteCore\Ads
 */

declare(strict_types=1);

namespace DNorteCore\Ads;

use DNorteCore\Database\DatabaseManager;

final class CampaignHistoryRepository {

	public function __construct( private readonly DatabaseManager $database ) {
	}

	public function record( int $campaignId, string $campaignName, string $action, string $actor, string $details = '' ): void {
		$this->database->insert(
			$this->database->table( 'ad_campaign_history' ),
			array(
				'campaign_id'   => $campaignId,
				'campaign_name' => $campaignName,
				'action'        => $action,
				'actor'         => $actor,
				'details'       => $details,
				'created_at'    => gmdate( 'Y-m-d H:i:s' ),
			)
		);
	}

	/**
	 * @return list<CampaignHistoryEntry>
	 */
	public function recent( int $limit = 100 ): array {
		$table = $this->database->table( 'ad_campaign_history' );

		$rows = $this->database->select(
			"SELECT * FROM {$table} ORDER BY id DESC LIMIT %d",
			array( $limit )
		);

		return array_map(
			static fn ( array $row ): CampaignHistoryEntry => new CampaignHistoryEntry(
				(int) $row['id'],
				(int) $row['campaign_id'],
				(string) $row['campaign_name'],
				(string) $row['action'],
				(string) $row['actor'],
				(string) $row['details'],
				(string) $row['created_at']
			),
			$rows
		);
	}
}
