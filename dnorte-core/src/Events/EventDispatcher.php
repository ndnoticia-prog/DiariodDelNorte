<?php
/**
 * Bus de eventos interno de la plataforma (no un wrapper de hooks de WordPress).
 *
 * Sirve para que dos módulos de dnorte-core se comuniquen sin conocerse entre sí
 * (ej. un módulo de anuncios reaccionando a que un bloque de portada se renderizó,
 * sin que el módulo de bloques sepa que el de anuncios existe). Si no hay listeners
 * para un evento, dispatch() simplemente no hace nada.
 *
 * @package DNorteCore\Events
 */

declare(strict_types=1);

namespace DNorteCore\Events;

final class EventDispatcher {

	/** @var array<string, list<callable>> */
	private array $listeners = array();

	public function listen( string $eventName, callable $listener ): void {
		$this->listeners[ $eventName ][] = $listener;
	}

	public function dispatch( Event $event ): void {
		foreach ( $this->listeners[ $event->name() ] ?? array() as $listener ) {
			$listener( $event );
		}
	}
}
