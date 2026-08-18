<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Seo\Schema;

use DNorteCore\Seo\Schema\OrganizationSchema;
use DNorteCore\Tests\Unit\TestCase;

final class OrganizationSchemaTest extends TestCase {

	public function test_build_returns_the_expected_shape(): void {
		$node = ( new OrganizationSchema( 'Diario del Norte', 'https://diariodelnorte.net/' ) )->build();

		self::assertSame(
			array(
				'@type' => 'Organization',
				'@id'   => 'https://diariodelnorte.net/#organization',
				'name'  => 'Diario del Norte',
				'url'   => 'https://diariodelnorte.net/',
			),
			$node
		);
	}
}
