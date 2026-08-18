<?php
/**
 * @package DNorteCore\Seo\Meta
 */

declare(strict_types=1);

namespace DNorteCore\Seo\Meta;

use DNorteCore\Seo\Context\SeoContext;

final class MetaTagsRenderer {

	public function __construct(
		private readonly RobotsMetaBuilder $robots,
		private readonly OpenGraphBuilder $openGraph,
		private readonly TwitterCardBuilder $twitter
	) {
	}

	public function render( SeoContext $context ): string {
		$lines = array(
			sprintf( '<meta name="robots" content="%s" />', esc_attr( $this->robots->build( $context ) ) ),
			sprintf( '<link rel="canonical" href="%s" />', esc_url( $context->canonicalUrl ) ),
		);

		foreach ( $this->openGraph->build( $context ) as $property => $content ) {
			$lines[] = sprintf( '<meta property="%s" content="%s" />', esc_attr( $property ), esc_attr( $content ) );
		}

		foreach ( $this->twitter->build( $context ) as $name => $content ) {
			$lines[] = sprintf( '<meta name="%s" content="%s" />', esc_attr( $name ), esc_attr( $content ) );
		}

		return implode( "\n", $lines ) . "\n";
	}
}
