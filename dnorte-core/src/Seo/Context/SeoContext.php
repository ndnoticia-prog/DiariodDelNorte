<?php
/**
 * Única fuente de verdad de los datos SEO de la página actual (singular, home,
 * archivo, búsqueda, 404). Meta tags, OpenGraph, Twitter Cards y Schema.org se
 * construyen todos a partir de la misma instancia — nunca resuelven el contexto por
 * separado, para que no puedan divergir entre sí.
 *
 * @package DNorteCore\Seo\Context
 */

declare(strict_types=1);

namespace DNorteCore\Seo\Context;

use WP_Post;

final class SeoContext {

	public function __construct(
		public readonly string $title,
		public readonly string $description,
		public readonly string $canonicalUrl,
		public readonly bool $noindex,
		public readonly string $ogType,
		public readonly ?string $imageUrl,
		public readonly ?WP_Post $post = null
	) {
	}

	public function isArticle(): bool {
		return $this->ogType === 'article' && $this->post !== null;
	}
}
