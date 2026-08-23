<?php
/**
 * @package DNorteCore\Workflow\Notes
 */

declare(strict_types=1);

namespace DNorteCore\Workflow\Notes;

use DNorteCore\Database\DatabaseManager;

final class EditorialNoteRepository {

	public function __construct( private readonly DatabaseManager $database ) {
	}

	public function add( int $postId, int $authorId, string $type, string $body ): int {
		return $this->database->insert(
			$this->database->table( 'editorial_notes' ),
			array(
				'post_id'    => $postId,
				'author_id'  => $authorId,
				'type'       => $type,
				'body'       => $body,
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
			)
		);
	}

	/**
	 * @return list<EditorialNote>
	 */
	public function forPost( int $postId ): array {
		$table = $this->database->table( 'editorial_notes' );
		$rows  = $this->database->select(
			"SELECT * FROM {$table} WHERE post_id = %d ORDER BY created_at ASC",
			array( $postId )
		);

		return array_map(
			static fn ( array $row ): EditorialNote => new EditorialNote(
				(int) $row['id'],
				(int) $row['post_id'],
				(int) $row['author_id'],
				(string) $row['type'],
				(string) $row['body'],
				(string) $row['created_at']
			),
			$rows
		);
	}
}
