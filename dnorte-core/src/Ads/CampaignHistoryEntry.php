<?php
/**
 * @package DNorteCore\Ads
 */

declare(strict_types=1);

namespace DNorteCore\Ads;

final class CampaignHistoryEntry {

	public function __construct(
		public readonly int $id,
		public readonly int $campaignId,
		public readonly string $campaignName,
		public readonly string $action,
		public readonly string $actor,
		public readonly string $details,
		public readonly string $createdAt
	) {
	}
}
