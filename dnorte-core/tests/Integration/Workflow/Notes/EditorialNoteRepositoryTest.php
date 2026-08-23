<?php
/**
 * @package DNorteCore\Tests\Integration
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Integration\Workflow\Notes;

use DNorteCore\Database\DatabaseManager;
use DNorteCore\Workflow\Notes\EditorialNoteRepository;
use WP_UnitTestCase;

final class EditorialNoteRepositoryTest extends WP_UnitTestCase {

	private EditorialNoteRepository $notes;

	protected function setUp(): void {
		parent::setUp();

		global $wpdb;
		$this->notes = new EditorialNoteRepository( new DatabaseManager( $wpdb ) );
	}

	public function test_add_then_for_post_returns_the_note(): void {
		$postId = self::factory()->post->create();

		$noteId = $this->notes->add( $postId, 1, 'general', 'Revisar la ortografía del titular.' );

		self::assertGreaterThan( 0, $noteId );

		$notes = $this->notes->forPost( $postId );

		self::assertCount( 1, $notes );
		self::assertSame( $postId, $notes[0]->postId );
		self::assertSame( 1, $notes[0]->authorId );
		self::assertSame( 'general', $notes[0]->type );
		self::assertSame( 'Revisar la ortografía del titular.', $notes[0]->body );
	}

	public function test_for_post_returns_notes_in_chronological_order(): void {
		$postId = self::factory()->post->create();

		$this->notes->add( $postId, 1, 'general', 'Primera nota' );
		$this->notes->add( $postId, 1, 'correction_request', 'Segunda nota' );

		$notes = $this->notes->forPost( $postId );

		self::assertCount( 2, $notes );
		self::assertSame( 'Primera nota', $notes[0]->body );
		self::assertSame( 'Segunda nota', $notes[1]->body );
	}

	public function test_for_post_only_returns_notes_for_that_post(): void {
		$postIdA = self::factory()->post->create();
		$postIdB = self::factory()->post->create();

		$this->notes->add( $postIdA, 1, 'general', 'Nota de A' );
		$this->notes->add( $postIdB, 1, 'general', 'Nota de B' );

		$notesForA = $this->notes->forPost( $postIdA );

		self::assertCount( 1, $notesForA );
		self::assertSame( 'Nota de A', $notesForA[0]->body );
	}
}
