<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Ads;

use DNorteCore\Ads\ContentParagraphInjector;
use DNorteCore\Tests\Unit\TestCase;

final class ContentParagraphInjectorTest extends TestCase {

	private const CONTENT = '<p>Uno.</p><p>Dos.</p><p>Tres.</p><p>Cuatro.</p>';

	public function test_it_inserts_right_after_the_requested_paragraph(): void {
		$result = ( new ContentParagraphInjector() )->insertAfterParagraph( self::CONTENT, '<div>AD</div>', 3 );

		self::assertSame(
			'<p>Uno.</p><p>Dos.</p><p>Tres.</p><div>AD</div><p>Cuatro.</p>',
			$result
		);
	}

	public function test_it_inserts_after_the_first_paragraph(): void {
		$result = ( new ContentParagraphInjector() )->insertAfterParagraph( self::CONTENT, '<div>AD</div>', 1 );

		self::assertSame(
			'<p>Uno.</p><div>AD</div><p>Dos.</p><p>Tres.</p><p>Cuatro.</p>',
			$result
		);
	}

	public function test_it_does_nothing_when_the_content_has_fewer_paragraphs_than_requested(): void {
		$result = ( new ContentParagraphInjector() )->insertAfterParagraph( self::CONTENT, '<div>AD</div>', 10 );

		self::assertSame( self::CONTENT, $result );
	}

	public function test_it_does_nothing_for_an_empty_insertion(): void {
		$result = ( new ContentParagraphInjector() )->insertAfterParagraph( self::CONTENT, '', 2 );

		self::assertSame( self::CONTENT, $result );
	}

	public function test_it_does_nothing_for_a_paragraph_number_below_one(): void {
		$result = ( new ContentParagraphInjector() )->insertAfterParagraph( self::CONTENT, '<div>AD</div>', 0 );

		self::assertSame( self::CONTENT, $result );
	}

	public function test_it_only_counts_closing_p_tags_not_other_markup(): void {
		$content = '<p>Uno.</p><figure><img src="x.jpg" /></figure><p>Dos.</p><p>Tres.</p>';

		$result = ( new ContentParagraphInjector() )->insertAfterParagraph( $content, '<div>AD</div>', 2 );

		self::assertSame(
			'<p>Uno.</p><figure><img src="x.jpg" /></figure><p>Dos.</p><div>AD</div><p>Tres.</p>',
			$result
		);
	}
}
