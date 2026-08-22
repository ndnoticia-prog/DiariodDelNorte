<?php
/**
 * Le añade las directivas Sitemap: a robots.txt. No reimplementa el sitemap general:
 * WordPress core expone wp-sitemap.xml (y sus sub-sitemaps) desde la 5.5, mantenido y
 * con el protocolo de sitemaps.org correctamente implementado — reescribirlo sería
 * duplicar código sin necesidad. El sitemap de noticias (sitemap-news.xml) sí es
 * propio — ver Sitemap\NewsSitemapController. Mismo criterio que ND Platform.
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

		$lines = array(
			'Sitemap: ' . home_url( '/wp-sitemap.xml' ),
			'Sitemap: ' . home_url( '/sitemap-news.xml' ),
		);

		foreach ( $lines as $line ) {
			if ( ! str_contains( $output, $line ) ) {
				$output = rtrim( $output ) . "\n" . $line . "\n";
			}
		}

		return $output;
	}
}
