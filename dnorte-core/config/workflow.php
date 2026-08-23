<?php
/**
 * Configuración del módulo de workflow editorial. Cargado automáticamente bajo la
 * clave "workflow" (ej. Config::get('workflow.shift_roles')).
 *
 * @package DNorteCore
 */

declare(strict_types=1);

return array(
	// Roles de turno disponibles en el panel de asignación — clave interna => etiqueta
	// visible. Ajustar según cómo organice sus turnos la redacción de Diario del
	// Norte.
	'shift_roles' => array(
		'editor_en_turno' => 'Editor en turno',
		'redactor_en_turno' => 'Redactor en turno',
		'community_manager' => 'Community manager',
	),
);
