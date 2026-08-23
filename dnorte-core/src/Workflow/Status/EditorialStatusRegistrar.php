<?php
/**
 * Registra los estados editoriales propios como ADICIONALES a los nativos de
 * WordPress (draft, pending, publish, ...) — nunca los sustituye. Mismo criterio que
 * ND Platform.
 *
 * @package DNorteCore\Workflow\Status
 */

declare(strict_types=1);

namespace DNorteCore\Workflow\Status;

final class EditorialStatusRegistrar {

	public const IN_REVIEW     = 'dnorte_in_review';
	public const NEEDS_CHANGES = 'dnorte_needs_changes';

	public function register(): void {
		register_post_status(
			self::IN_REVIEW,
			array(
				'label'                     => _x( 'En revisión', 'estado editorial', 'dnorte-core' ),
				'public'                    => false,
				'internal'                  => true,
				'protected'                 => true,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: número de artículos en revisión. */
				'label_count'               => _n_noop( 'En revisión <span class="count">(%s)</span>', 'En revisión <span class="count">(%s)</span>', 'dnorte-core' ),
			)
		);

		register_post_status(
			self::NEEDS_CHANGES,
			array(
				'label'                     => _x( 'Necesita cambios', 'estado editorial', 'dnorte-core' ),
				'public'                    => false,
				'internal'                  => true,
				'protected'                 => true,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: número de artículos que necesitan cambios. */
				'label_count'               => _n_noop( 'Necesita cambios <span class="count">(%s)</span>', 'Necesita cambios <span class="count">(%s)</span>', 'dnorte-core' ),
			)
		);
	}
}
