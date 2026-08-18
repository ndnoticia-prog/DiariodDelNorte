<?php
/**
 * @package DNorteCore\Seo\Schema
 */

declare(strict_types=1);

namespace DNorteCore\Seo\Schema;

final class WebSiteSchema {

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
			'@type'           => 'WebSite',
			'@id'             => $this->siteUrl . '#website',
			'name'            => $this->siteName,
			'url'             => $this->siteUrl,
			'publisher'       => array( '@id' => $this->siteUrl . '#organization' ),
			'potentialAction' => array(
				array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => $this->siteUrl . '?s={search_term_string}',
					),
					'query-input' => 'required name=search_term_string',
				),
			),
		);
	}
}
