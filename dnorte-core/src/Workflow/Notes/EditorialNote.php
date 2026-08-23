<?php
/**
 * @package DNorteCore\Workflow\Notes
 */

declare(strict_types=1);

namespace DNorteCore\Workflow\Notes;

final class EditorialNote {

	public function __construct(
		public readonly int $id,
		public readonly int $postId,
		public readonly int $authorId,
		public readonly string $type,
		public readonly string $body,
		public readonly string $createdAt
	) {
	}
}
