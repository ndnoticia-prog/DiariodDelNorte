<?php
/**
 * Único punto del plugin que llama a add_action/add_filter/do_action/apply_filters de WordPress.
 *
 * Permite registrar listeners de forma diferida (antes de que WordPress esté cargado —
 * útil en pruebas unitarias con Brain Monkey) y desregistrarlos por token.
 *
 * @package DNorteCore\Hooks
 */

declare(strict_types=1);

namespace DNorteCore\Hooks;

final class HookManager {

	/**
	 * @var array<string, array{type: string, hook: string, callback: callable, priority: int, args: int}>
	 */
	private array $pending = array();

	private bool $flushed = false;

	public function addAction( string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1 ): string {
		return $this->register( 'action', $hook, $callback, $priority, $acceptedArgs );
	}

	public function addFilter( string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1 ): string {
		return $this->register( 'filter', $hook, $callback, $priority, $acceptedArgs );
	}

	private function register( string $type, string $hook, callable $callback, int $priority, int $acceptedArgs ): string {
		$token = uniqid( 'dnorte_hook_', true );

		$this->pending[ $token ] = array(
			'type'     => $type,
			'hook'     => $hook,
			'callback' => $callback,
			'priority' => $priority,
			'args'     => $acceptedArgs,
		);

		if ( $this->flushed ) {
			$this->wire( $this->pending[ $token ] );
		}

		return $token;
	}

	/**
	 * @param non-empty-string $hook
	 */
	public function doAction( string $hook, mixed ...$args ): void {
		do_action( $hook, ...$args );
	}

	/**
	 * @param non-empty-string $hook
	 */
	public function applyFilters( string $hook, mixed $value, mixed ...$args ): mixed {
		return apply_filters( $hook, $value, ...$args );
	}

	public function remove( string $token ): void {
		if ( ! isset( $this->pending[ $token ] ) ) {
			return;
		}

		$entry = $this->pending[ $token ];

		if ( $entry['type'] === 'action' ) {
			remove_action( $entry['hook'], $entry['callback'], $entry['priority'] );
		} else {
			remove_filter( $entry['hook'], $entry['callback'], $entry['priority'] );
		}

		unset( $this->pending[ $token ] );
	}

	/**
	 * Traduce todos los listeners registrados hasta ahora a add_action/add_filter reales.
	 * Llamadas posteriores a addAction/addFilter se conectan de inmediato.
	 */
	public function flush(): void {
		foreach ( $this->pending as $entry ) {
			$this->wire( $entry );
		}

		$this->flushed = true;
	}

	/**
	 * @param array{type: string, hook: string, callback: callable, priority: int, args: int} $entry
	 */
	private function wire( array $entry ): void {
		if ( $entry['type'] === 'action' ) {
			add_action( $entry['hook'], $entry['callback'], $entry['priority'], $entry['args'] );
		} else {
			add_filter( $entry['hook'], $entry['callback'], $entry['priority'], $entry['args'] );
		}
	}
}
