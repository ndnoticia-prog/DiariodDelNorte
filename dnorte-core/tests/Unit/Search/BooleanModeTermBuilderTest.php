<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Search;

use DNorteCore\Search\BooleanModeTermBuilder;
use DNorteCore\Tests\Unit\TestCase;

final class BooleanModeTermBuilderTest extends TestCase {

	public function test_a_single_word_gets_a_trailing_wildcard(): void {
		self::assertSame( 'elecciones*', ( new BooleanModeTermBuilder() )->build( 'elecciones' ) );
	}

	public function test_multiple_words_each_get_their_own_wildcard(): void {
		self::assertSame(
			'concejo* municipal*',
			( new BooleanModeTermBuilder() )->build( 'concejo municipal' )
		);
	}

	public function test_extra_whitespace_between_words_is_collapsed(): void {
		self::assertSame(
			'concejo* municipal*',
			( new BooleanModeTermBuilder() )->build( "  concejo   municipal  \n" )
		);
	}

	public function test_reserved_boolean_mode_operators_are_stripped_from_each_word(): void {
		self::assertSame(
			'concejo* municipal*',
			( new BooleanModeTermBuilder() )->build( '+concejo* -"municipal"' )
		);
	}

	public function test_a_word_made_up_only_of_reserved_operators_is_dropped(): void {
		self::assertSame( 'concejo*', ( new BooleanModeTermBuilder() )->build( 'concejo ***' ) );
	}

	public function test_an_empty_term_produces_an_empty_string(): void {
		self::assertSame( '', ( new BooleanModeTermBuilder() )->build( '   ' ) );
	}
}
