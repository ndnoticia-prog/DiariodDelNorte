<?php
/**
 * Le añade la directiva Sitemap: a robots.txt. No reimplementa el sitemap general:
 * WordPress core expone wp-sitemap.xml (y sus sub-sitemaps) desde la 5.5, mantenido y
 * con el protocolo de sitemaps.org correctamente implementado — reescribirlo sería
 * duplicar código sin necesidad. Mismo criterio que ND Platform.
 *
 * @package DNorteCore\Seo\Robots
 */

declare(strict_types=1);

namespace DNorteCore\Seo\Robots;

final class RobotsTxtBuilder {

	public function filter( string $output, bool $public ): string {
		if ( ! $public ) {
			return $output;
		}

		$sitemapLine = 'Sitemap: ' . home_url( '/wp-sitemap.xml' );

		if ( str_contains( $output, $sitemapLine ) ) {
			return $output;
		}

		return rtrim( $output ) . "\n" . $sitemapLine . "\n";
	}
}
