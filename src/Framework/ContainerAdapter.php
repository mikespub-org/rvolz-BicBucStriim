<?php

namespace BicBucStriim\Framework;

use Psr\Container\ContainerInterface;
use Closure;

/**
 * An adapter that wraps a PSR-11 container to provide a non-standard `set()` method.
 * This allows the rest of the application (specifically AppTrait) to remain
 * compatible with containers that do not natively support setting values at runtime,
 * like Symfony's or Laravel's.
 */
class ContainerAdapter implements ContainerInterface
{
    /** @var ContainerInterface The actual DI container from the framework. */
    private ContainerInterface $container;

    /** @var array<string, mixed> Storage for runtime values. */
    private array $runtimeValues = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     * It first checks for runtime values, then falls back to the wrapped container.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed Entry.
     */
    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->runtimeValues)) {
            $value = $this->runtimeValues[$id];

            // If the value is a callable (like a factory), resolve it.
            // We check for Closure specifically, as this is how PHP-DI treats factories.
            // We also need to check if the underlying container has a 'call' method.
            if ($value instanceof Closure && method_exists($this->container, 'call')) {
                // Resolve the factory and store the resulting instance
                // to ensure it's a singleton for this request.
                $resolvedValue = $this->container->call($value);
                $this->runtimeValues[$id] = $resolvedValue;
                return $resolvedValue;
            }

            return $value;
        }
        return $this->container->get($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     */
    public function has(string $id): bool
    {
        if (array_key_exists($id, $this->runtimeValues)) {
            return true;
        }
        return $this->container->has($id);
    }

    /**
     * Sets a value at runtime. This is a non-standard method.
     */
    public function set(string $id, mixed $value): void
    {
        $this->runtimeValues[$id] = $value;
    }
}
