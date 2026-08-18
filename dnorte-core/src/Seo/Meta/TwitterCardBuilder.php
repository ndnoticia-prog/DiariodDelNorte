<?php
/**
 * @package DNorteCore\Seo\Meta
 */

declare(strict_types=1);

namespace DNorteCore\Seo\Meta;

use DNorteCore\Seo\Context\SeoContext;

final class TwitterCardBuilder {

	/**
	 * @return array<string, string>
	 */
	public function build( SeoContext $context ): array {
		$tags = array(
			'twitter:card'  => $context->imageUrl !== null ? 'summary_large_image' : 'summary',
			'twitter:title' => $context->title,
		);

		if ( $context->description !== '' ) {
			$tags['twitter:description'] = $context->description;
		}

		if ( $context->imageUrl !== null ) {
			$tags['twitter:image'] = $context->imageUrl;
		}

		return $tags;
	}
}
