<?php
/**
 * @package DNorteCore\Seo\Schema
 */

declare(strict_types=1);

namespace DNorteCore\Seo\Schema;

final class OrganizationSchema {

	public function __construct(
		private readonly string $siteName,
		private readonly string $siteUrl
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function build(): array {
		return array(
			'@type' => 'Organization',
			'@id'   => $this->siteUrl . '#organization',
			'name'  => $this->siteName,
			'url'   => $this->siteUrl,
		);
	}
}
