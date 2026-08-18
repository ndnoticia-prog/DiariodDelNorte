<?php
/**
 * @package DNorteCore\Seo\Schema
 */

declare(strict_types=1);

namespace DNorteCore\Seo\Schema;

final class BreadcrumbListSchema {

	/**
	 * @param list<array{name: string, url: string}> $items
	 * @return array<string, mixed>
	 */
	public function build( array $items ): array {
		$listItems = array();

		foreach ( $items as $position => $item ) {
			$listItems[] = array(
				'@type'    => 'ListItem',
				'position' => $position + 1,
				'name'     => $item['name'],
				'item'     => $item['url'],
			);
		}

		return array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $listItems,
		);
	}
}
