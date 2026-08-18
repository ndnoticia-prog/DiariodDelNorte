<?php
/**
 * @package DNorteCore\Seo\Meta
 */

declare(strict_types=1);

namespace DNorteCore\Seo\Meta;

use DNorteCore\Seo\Context\SeoContext;

final class RobotsMetaBuilder {

	public function build( SeoContext $context ): string {
		if ( $context->noindex ) {
			return 'noindex, nofollow';
		}

		// max-image-preview:large: requisito de elegibilidad para Google Discover,
		// sin coste para páginas que nunca aparezcan ahí.
		return 'index, follow, max-image-preview:large';
	}
}
