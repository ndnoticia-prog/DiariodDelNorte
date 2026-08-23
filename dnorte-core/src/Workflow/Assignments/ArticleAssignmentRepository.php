<?php
/**
 * Asignación de un artículo a un periodista — post meta (`_dnorte_assigned_to`) en
 * vez de una tabla propia: es una relación 1:1 simple que post meta ya resuelve e
 * indexa. Mismo criterio que ND Platform.
 *
 * @package DNorteCore\Workflow\Assignments
 */

declare(strict_types=1);

namespace DNorteCore\Workflow\Assignments;

final class ArticleAssignmentRepository {

	private const META_KEY = '_dnorte_assigned_to';

	public function assign( int $postId, int $userId ): void {
		update_post_meta( $postId, self::META_KEY, $userId );
	}

	public function unassign( int $postId ): void {
		delete_post_meta( $postId, self::META_KEY );
	}

	public function assignedTo( int $postId ): ?int {
		$value  = get_post_meta( $postId, self::META_KEY, true );
		$userId = (int) $value;

		return $userId > 0 ? $userId : null;
	}
}
