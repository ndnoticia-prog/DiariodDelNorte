<?php
/**
 * Repositorio de configuración en memoria, cargado desde arrays PHP en `config/`.
 *
 * @package DNorteCore\Config
 */

declare(strict_types=1);

namespace DNorteCore\Config;

final class Config {

	/** @var array<string, mixed> */
	private array $items = array();

	/**
	 * @param array<string, mixed> $items
	 */
	public function __construct( array $items = array() ) {
		$this->items = $items;
	}

	/**
	 * Carga todos los ficheros `*.php` de un directorio; cada uno debe devolver un array
	 * y se indexa bajo su nombre de fichero sin extensión (ej. `config/app.php` → clave `app`).
	 */
	public function loadDirectory( string $directory ): void {
		$files = glob( rtrim( $directory, '/' ) . '/*.php' );

		foreach ( $files === false ? array() : $files as $file ) {
			$key = basename( $file, '.php' );
			/** @var array<string, mixed> $value */
			$value               = require $file;
			$this->items[ $key ] = $value;
		}
	}

	public function get( string $key, mixed $default = null ): mixed {
		$segments = explode( '.', $key );
		$value    = $this->items;

		foreach ( $segments as $segment ) {
			if ( ! is_array( $value ) || ! array_key_exists( $segment, $value ) ) {
				return $default;
			}

			$value = $value[ $segment ];
		}

		return $value;
	}

	public function set( string $key, mixed $value ): void {
		$segments = explode( '.', $key );
		$target   = &$this->items;

		foreach ( $segments as $i => $segment ) {
			if ( $i === count( $segments ) - 1 ) {
				$target[ $segment ] = $value;

				return;
			}

			if ( ! isset( $target[ $segment ] ) || ! is_array( $target[ $segment ] ) ) {
				$target[ $segment ] = array();
			}

			$target = &$target[ $segment ];
		}
	}

	public function has( string $key ): bool {
		return $this->get( $key, '__dnorte_missing__' ) !== '__dnorte_missing__';
	}
}
