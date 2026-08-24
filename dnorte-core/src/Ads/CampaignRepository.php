<?php
/**
 * @package DNorteCore\Ads
 */

declare(strict_types=1);

namespace DNorteCore\Ads;

use DateTimeImmutable;
use DNorteCore\Database\DatabaseManager;

final class CampaignRepository {

	public function __construct( private readonly DatabaseManager $database ) {
	}

	/**
	 * @return list<Campaign>
	 */
	public function all(): array {
		$table = $this->database->table( 'ad_campaigns' );
		$rows  = $this->database->select( "SELECT * FROM {$table} ORDER BY priority DESC, id ASC" );

		return array_map( fn ( array $row ): Campaign => $this->hydrate( $row ), $rows );
	}

	public function find( int $id ): ?Campaign {
		$table = $this->database->table( 'ad_campaigns' );
		$row   = $this->database->selectOne( "SELECT * FROM {$table} WHERE id = %d", array( $id ) );

		return $row !== null ? $this->hydrate( $row ) : null;
	}

	/**
	 * La campaña activa con más prioridad entre las que se dirigen a este espacio
	 * (y, si tienen categorías configuradas, a las del contenido actual) — null si
	 * ninguna aplica ahora mismo. Empate de prioridad se resuelve por id ascendente
	 * (la campaña más antigua), para un resultado determinista y probable.
	 *
	 * @param list<string> $categorySlugs
	 */
	public function forZone( string $zoneKey, DateTimeImmutable $now, array $categorySlugs = array() ): ?Campaign {
		$candidates = array_values(
			array_filter(
				$this->all(),
				static fn ( Campaign $campaign ): bool =>
					$campaign->isActiveAt( $now )
					&& $campaign->appliesToZone( $zoneKey )
					&& $campaign->appliesToCategories( $categorySlugs )
			)
		);

		if ( $candidates === array() ) {
			return null;
		}

		usort(
			$candidates,
			static function ( Campaign $a, Campaign $b ): int {
				$byPriority = $b->priority <=> $a->priority;

				return $byPriority !== 0 ? $byPriority : $a->id <=> $b->id;
			}
		);

		return $candidates[0];
	}

	/**
	 * Crea la campaña si $campaign->id es 0, o reemplaza la existente si no —
	 * un único método en vez de create()/update() separados porque
	 * Ads\AdsAdminPage ya arma un Campaign completo en ambos casos (con id=0 para
	 * una campaña nueva) y no hay ningún otro llamador que necesite la distinción.
	 *
	 * @return int El id de la campaña (nuevo o el mismo que ya tenía).
	 */
	public function save( Campaign $campaign ): int {
		$table = $this->database->table( 'ad_campaigns' );
		$data  = $this->toRow( $campaign );

		if ( $campaign->id > 0 ) {
			$this->database->update( $table, $data, array( 'id' => $campaign->id ) );

			return $campaign->id;
		}

		$data['created_at'] = gmdate( 'Y-m-d H:i:s' );

		return $this->database->insert( $table, $data );
	}

	public function delete( int $id ): void {
		$this->database->delete( $this->database->table( 'ad_campaigns' ), array( 'id' => $id ) );
	}

	/**
	 * Incremento atómico (`clicks = clicks + 1`), no lectura-modificación-escritura:
	 * evita perder eventos si dos beacons llegan casi a la vez (ver
	 * Ads\CampaignEventController).
	 */
	public function recordImpression( int $id ): void {
		$table = $this->database->table( 'ad_campaigns' );
		$this->database->statement( "UPDATE {$table} SET impressions = impressions + 1 WHERE id = %d", array( $id ) );
	}

	public function recordClick( int $id ): void {
		$table = $this->database->table( 'ad_campaigns' );
		$this->database->statement( "UPDATE {$table} SET clicks = clicks + 1 WHERE id = %d", array( $id ) );
	}

	/**
	 * Añade un id de adjunto (Biblioteca de medios) a la lista de evidencia de la
	 * campaña, sin duplicarlo si ya estaba.
	 */
	public function addEvidence( int $id, int $attachmentId ): void {
		$campaign = $this->find( $id );

		if ( $campaign === null || in_array( $attachmentId, $campaign->evidenceIds, true ) ) {
			return;
		}

		$table = $this->database->table( 'ad_campaigns' );
		$this->database->update(
			$table,
			array(
				'evidence_ids' => implode( ',', array( ...$campaign->evidenceIds, $attachmentId ) ),
				'updated_at'   => gmdate( 'Y-m-d H:i:s' ),
			),
			array( 'id' => $id )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function toRow( Campaign $campaign ): array {
		return array(
			'name'              => $campaign->name,
			'advertiser'        => $campaign->advertiser,
			'type'              => $campaign->type,
			'enabled'           => $campaign->enabled ? 1 : 0,
			'priority'          => $campaign->priority,
			'zones'             => implode( ',', $campaign->zones ),
			'categories'        => implode( ',', $campaign->categories ),
			'starts_at'         => $campaign->startsAt,
			'ends_at'           => $campaign->endsAt,
			'html'              => $campaign->html,
			'adsense_client_id' => $campaign->adsenseClientId,
			'adsense_slot_id'   => $campaign->adsenseSlotId,
			'image_url'         => $campaign->imageUrl,
			'link_url'          => $campaign->linkUrl,
			'video_url'         => $campaign->videoUrl,
			'description'       => $campaign->description,
			'gam_ad_unit_path'  => $campaign->gamAdUnitPath,
			'gam_sizes'         => $campaign->gamSizes,
			'updated_at'        => gmdate( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function hydrate( array $row ): Campaign {
		return new Campaign(
			(int) $row['id'],
			(string) $row['name'],
			(string) $row['advertiser'],
			(string) $row['type'],
			(int) $row['enabled'] === 1,
			(int) $row['priority'],
			$this->splitList( (string) $row['zones'] ),
			$this->splitList( (string) $row['categories'] ),
			$row['starts_at'] !== null ? (string) $row['starts_at'] : null,
			$row['ends_at'] !== null ? (string) $row['ends_at'] : null,
			(string) $row['html'],
			(string) $row['adsense_client_id'],
			(string) $row['adsense_slot_id'],
			(string) $row['image_url'],
			(string) $row['link_url'],
			(int) $row['impressions'],
			(int) $row['clicks'],
			array_map( 'intval', $this->splitList( (string) $row['evidence_ids'] ) ),
			(string) $row['description'],
			(string) $row['video_url'],
			(string) $row['gam_ad_unit_path'],
			(string) $row['gam_sizes']
		);
	}

	/**
	 * @return list<string>
	 */
	private function splitList( string $value ): array {
		if ( trim( $value ) === '' ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'trim', explode( ',', $value ) ),
				static fn ( string $item ): bool => $item !== ''
			)
		);
	}
}
