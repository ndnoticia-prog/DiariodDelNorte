<?php
/**
 * Contenedor de inyección de dependencias con autowiring.
 *
 * Único punto del plugin donde se instancian objetos "a mano" (fuera de un
 * ServiceProvider). Nada más debería usar `new` para construir un servicio.
 *
 * @package DNorteCore\Container
 */

declare(strict_types=1);

namespace DNorteCore\Container;

use DNorteCore\Container\Exceptions\UnresolvableParameterException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

final class Container
{
    /** @var array<string, array{concrete: callable|string, shared: bool}> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    public function bind(string $abstract, callable|string $concrete, bool $shared = false): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    public function singleton(string $abstract, callable|string $concrete): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]) || class_exists($id);
    }

    public function get(string $id): mixed
    {
        return $this->make($id);
    }

    /**
     * @param array<string, mixed> $parameters Valores explícitos para parámetros del constructor,
     *                                          indexados por nombre de parámetro.
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $binding = $this->bindings[$abstract];
            $concrete = $binding['concrete'];

            $object = is_callable($concrete)
                ? $concrete($this, $parameters)
                : $this->build($concrete, $parameters);

            if ($binding['shared']) {
                $this->instances[$abstract] = $object;
            }

            return $object;
        }

        return $this->build($abstract, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function build(string $className, array $parameters = []): object
    {
        if (! class_exists($className)) {
            throw new UnresolvableParameterException(
                sprintf('No es posible resolver "%s": la clase no existe y no hay binding registrado.', $className)
            );
        }

        $reflection = new ReflectionClass($className);

        if (! $reflection->isInstantiable()) {
            throw new UnresolvableParameterException(
                sprintf('"%s" no es instanciable (interfaz/clase abstracta sin binding).', $className)
            );
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $className();
        }

        $dependencies = array_map(
            fn (ReflectionParameter $parameter) => $this->resolveParameter($parameter, $parameters, $className),
            $constructor->getParameters()
        );

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function resolveParameter(ReflectionParameter $parameter, array $parameters, string $className): mixed
    {
        $name = $parameter->getName();

        if (array_key_exists($name, $parameters)) {
            return $parameters[$name];
        }

        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            return $this->make($type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new UnresolvableParameterException(
            sprintf(
                'No es posible resolver el parámetro "%s" de %s: sin type-hint de clase, sin valor por defecto y sin binding explícito.',
                $name,
                $className
            )
        );
    }
}
