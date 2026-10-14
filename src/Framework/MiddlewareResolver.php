<?php

declare(strict_types=1);

namespace BicBucStriim\Framework;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Resolves a route's middleware entries into concrete PSR-15 middleware instances.
 *
 * The route map stores middleware as framework-agnostic class-strings (e.g.
 * RoleGuard::class) so the map is identical across all frameworks. Each host
 * framework needs concrete instances to run them through its pipeline, so this
 * resolver turns the abstract entries into MiddlewareInterface objects.
 *
 * It is deliberately framework-agnostic and shared by every integration (the
 * custom App, Symfony, Laravel). The container is OPTIONAL: when provided, a
 * class-string is resolved through it first (allowing constructor dependency
 * injection, as Slim does via its CallableResolver). When no container is given
 * — or the container does not know the class — the resolver falls back to a
 * RoleGuard special case (which needs a PSR-17 response factory) and finally to
 * a no-argument constructor.
 */
class MiddlewareResolver
{
    private ?ContainerInterface $container;
    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        ?ContainerInterface $container = null,
        ?ResponseFactoryInterface $responseFactory = null,
    ) {
        $this->container = $container;
        $this->responseFactory = $responseFactory ?? new Psr17Factory();
    }

    /**
     * Resolve a list of middleware entries into MiddlewareInterface instances.
     *
     * Entries that cannot be resolved into a MiddlewareInterface (e.g. a class
     * that does not implement the interface, or an unknown class-string with no
     * usable constructor) are skipped rather than fataling, so one bad entry
     * cannot take down the whole route.
     *
     * @param array<int, string|object> $middleware
     * @return array<int, MiddlewareInterface>
     */
    public function resolve(array $middleware): array
    {
        $resolved = [];
        foreach ($middleware as $entry) {
            $instance = $this->resolveOne($entry);
            if ($instance instanceof MiddlewareInterface) {
                $resolved[] = $instance;
            }
        }
        return $resolved;
    }

    /**
     * Resolve a route's middleware entries, prepending the group admin guard
     * for gated routes.
     *
     * This is the single, framework-agnostic spelling of the gated+route
     * middleware policy shared by the custom App, Symfony and Laravel
     * adapters: when $gated is true the RoleGuard class-string is prepended
     * before resolution (so container-provided guards still win via
     * resolve()), and duplicate guards are collapsed to a single instance.
     *
     * @param array<int, string|object> $middleware route-level entries
     * @return array<int, MiddlewareInterface>
     */
    public function resolveForRoute(bool $gated, array $middleware): array
    {
        $entries = array_values($middleware);
        if ($gated && !RoleGuard::hasGuard($entries)) {
            array_unshift($entries, RoleGuard::class);
        }
        return $this->resolve($entries);
    }

    /**
     * Resolve a single middleware entry.
     *
     * @param string|object $entry
     */
    private function resolveOne(string|object $entry): ?MiddlewareInterface
    {
        // Already an object: use as-is when it is valid PSR-15 middleware.
        if (is_object($entry)) {
            return $entry instanceof MiddlewareInterface ? $entry : null;
        }

        // A class-string: try the container first (optional dependency injection).
        $class = $entry;
        if ($this->container !== null && $this->container->has($class)) {
            $instance = $this->container->get($class);
            if ($instance instanceof MiddlewareInterface) {
                return $instance;
            }
        }

        // RoleGuard needs a PSR-17 response factory to build its 403 response.
        if ($class === RoleGuard::class || is_subclass_of($class, RoleGuard::class)) {
            return new $class($this->responseFactory);
        }

        // Last resort: a no-argument constructor.
        if (class_exists($class)) {
            $instance = new $class();
            return $instance instanceof MiddlewareInterface ? $instance : null;
        }

        return null;
    }
}
