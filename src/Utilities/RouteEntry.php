<?php

declare(strict_types=1);

namespace BicBucStriim\Utilities;

/**
 * Immutable snapshot of a single route map entry.
 *
 * Captures exactly the fields that downstream consumers (Slim, Symfony,
 * Laravel, and API introspection) need after the ActionRegistry has flattened
 * the action classes' route tuples and resolved the group gatekeeper flag.
 * The DTO is a read-only value object; it owns no behavior and no
 * persistence.
 */
final readonly class RouteEntry
{
    /**
     * @param array{0: class-string, 1: string}|\Closure $callable
     *        The production route handler. Either the array form
     *        [ActionClass::class, 'methodName'] (initialize()d via AppTrait and
     *        portable across every framework) or a context-free Closure (no
     *        action instance, so no initialize(); see ActionResolver::resolve()).
     *        Note: `callable` cannot be used in a native union type (reserved
     *        keyword), so the two concrete shapes are spelled out.
     */
    public function __construct(
        public string $name,
        public array|string $methods,
        public string $path,
        public array|\Closure $callable,
        public array $middleware = [],
        public bool $gated = false,
    ) {}

    public function withGated(bool $gated): self
    {
        if ($this->gated === $gated) {
            return $this;
        }

        return new self(
            $this->name,
            $this->methods,
            $this->path,
            $this->callable,
            $this->middleware,
            $gated,
        );
    }
}
