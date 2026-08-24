<?php
/**
 * @package DNorteCore\Ads
 */

declare(strict_types=1);

namespace DNorteCore\Ads;

use DateTimeImmutable;
use DateTimeZone;

final class Campaign {

	public const TYPE_HTML    = 'html';
	public const TYPE_ADSENSE = 'adsense';

	/**
	 * @param list<string> $zones Claves de espacio (config/ads.php) donde puede aparecer.
	 * @param list<string> $categories Slugs de categoría a los que se restringe;
	 *                                  lista vacía = todas las categorías.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly string $advertiser,
		public readonly string $type,
		public readonly bool $enabled,
		public readonly int $priority,
		public readonly array $zones,
		public readonly array $categories,
		public readonly ?string $startsAt,
		public readonly ?string $endsAt,
		public readonly string $html,
		public readonly string $adsenseClientId,
		public readonly string $adsenseSlotId
	) {
	}

	public function isActiveAt( DateTimeImmutable $moment ): bool {
		if ( ! $this->enabled ) {
			return false;
		}

		if ( $this->startsAt !== null && $moment < new DateTimeImmutable( $this->startsAt, new DateTimeZone( 'UTC' ) ) ) {
			return false;
		}

		if ( $this->endsAt !== null && $moment > new DateTimeImmutable( $this->endsAt, new DateTimeZone( 'UTC' ) ) ) {
			return false;
		}

		return true;
	}

	public function appliesToZone( string $zoneKey ): bool {
		return in_array( $zoneKey, $this->zones, true );
	}

	/**
	 * Sin categorías configuradas = se dirige a todas, incluidos los espacios
	 * sitewide (Cabecera/Inicio) que no tienen ningún artículo/categoría de
	 * contexto — ver Providers\AdsServiceProvider, que les pasa una lista vacía de
	 * categorías del post actual. Una campaña CON categorías configuradas nunca
	 * aparece en esos dos espacios (la intersección con una lista vacía siempre es
	 * vacía) — comportamiento correcto: "solo en Deportes" no tiene sentido fuera
	 * de un artículo de Deportes.
	 *
	 * @param list<string> $categorySlugs
	 */
	public function appliesToCategories( array $categorySlugs ): bool {
		if ( $this->categories === array() ) {
			return true;
		}

		return array_intersect( $this->categories, $categorySlugs ) !== array();
	}
}
