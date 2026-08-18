<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Seo\Schema;

use DNorteCore\Seo\Schema\WebSiteSchema;
use DNorteCore\Tests\Unit\TestCase;

final class WebSiteSchemaTest extends TestCase {

	public function test_build_references_the_organization_and_includes_a_search_action(): void {
		$node = ( new WebSiteSchema( 'Diario del Norte', 'https://diariodelnorte.net/' ) )->build();

		self::assertSame( 'WebSite', $node['@type'] );
		self::assertSame( array( '@id' => 'https://diariodelnorte.net/#organization' ), $node['publisher'] );
		self::assertSame(
			'https://diariodelnorte.net/?s={search_term_string}',
			$node['potentialAction'][0]['target']['urlTemplate']
		);
	}
}
