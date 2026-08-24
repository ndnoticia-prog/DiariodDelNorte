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
	public const TYPE_IMAGE   = 'image';

	/**
	 * @param list<string> $zones Claves de espacio (config/ads.php) donde puede aparecer.
	 * @param list<string> $categories Slugs de categoría a los que se restringe;
	 *                                  lista vacía = todas las categorías.
	 * @param list<int> $evidenceIds Ids de adjuntos de la Biblioteca de medios que
	 *                                prueban que la campaña corrió (capturas,
	 *                                contrato con el anunciante, etc.).
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
		public readonly string $adsenseSlotId,
		public readonly string $imageUrl = '',
		public readonly string $linkUrl = '',
		public readonly int $impressions = 0,
		public readonly int $clicks = 0,
		public readonly array $evidenceIds = array()
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

	/**
	 * Porcentaje de clics por impresión, 0 si todavía no hay impresiones (evita
	 * una división entre cero) — mismo dato que muestra la columna
	 * "Estadísticas" del panel ("767 impr. · 1 clics · 0.13% CTR").
	 */
	public function ctr(): float {
		if ( $this->impressions === 0 ) {
			return 0.0;
		}

		return ( $this->clicks / $this->impressions ) * 100;
	}
}
