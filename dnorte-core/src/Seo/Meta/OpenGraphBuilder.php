<?php
/**
 * @package DNorteCore\Seo\Meta
 */

declare(strict_types=1);

namespace DNorteCore\Seo\Meta;

use DNorteCore\Seo\Context\SeoContext;

final class OpenGraphBuilder {

	public function __construct( private readonly string $siteName ) {
	}

	/**
	 * @return array<string, string>
	 */
	public function build( SeoContext $context ): array {
		$tags = array(
			'og:type'      => $context->ogType,
			'og:title'     => $context->title,
			'og:url'       => $context->canonicalUrl,
			'og:site_name' => $this->siteName,
		);

		if ( $context->description !== '' ) {
			$tags['og:description'] = $context->description;
		}

		if ( $context->imageUrl !== null ) {
			$tags['og:image'] = $context->imageUrl;
		}

		return $tags;
	}
}
