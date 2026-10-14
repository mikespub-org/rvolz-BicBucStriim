<?php

namespace BicBucStriim\Utilities;

use BicBucStriim\Framework\RoleGuard;
use BicBucStriim\Utilities\RouteEntry;

/**
 * Class RouteUtil provides route utilities for callables and middleware
 */
class RouteUtil
{
    /**
     * Parse a route tuple into its parts.
     *
     * This is the framework-agnostic counterpart to mapSlim4Routes(): every
     * route definition uses the tuple format
     *
     *     name => [method(s), path, ...middleware(s), callable]
     *
     * where the callable is *always* the last element and everything between
     * the path and the callable is route-specific middleware (zero, one or
     * more). This method is the single source of truth for that parsing and is
     * shared by the Slim router (mapSlim4Routes) and the framework-agnostic
     * ActionRegistry consumed by the Symfony, Laravel and custom App adapters.
     *
     * @param array<mixed> $routeInfo a single route tuple
     * @return array{methods: array<string>|string, path: string, callable: array{0: class-string, 1: string}, middleware: array<int, mixed>}
     * @throws \InvalidArgumentException if the tuple is too short to contain method, path and callable
     */
    public static function parseRouteTuple(array $routeInfo): array
    {
        if (count($routeInfo) < 3) {
            throw new \InvalidArgumentException('Route tuple must contain at least method, path and callable.');
        }
        $methods = array_shift($routeInfo);
        $path = array_shift($routeInfo);
        // The callable is always the last element.
        $callable = array_pop($routeInfo);
        // Whatever remains in the middle is route-specific middleware (0, 1 or more).
        $middleware = array_values($routeInfo);

        return [
            'methods' => $methods,
            'path' => $path,
            'callable' => $callable,
            'middleware' => $middleware,
        ];
    }

    /**
     * Register all routes from the framework-agnostic route map onto a Slim app.
     *
     * This is the single Slim registration path for P4: it consumes the exact
     * same structured route map (registry->getRouteMap()) that the custom App,
     * Symfony and Laravel adapters use, so there is one source of truth and no
     * chance of the two builders drifting. Paths in the map are already
     * prefixed, so routes are registered flat (no Slim group/prefix needed).
     *
     * Middleware handling (preserves current Slim behavior):
     *  - Group-gated routes (gated === true) get the Slim $gatekeeper
     *    (GatekeeperMiddleware) attached, keeping the Twig 403 page.
     *  - Route-specific middleware class-strings are resolved for Slim: the
     *    framework-agnostic RoleGuard::class is presented as $gatekeeper so it
     *    also keeps the Twig 403 page; any other entry is passed through
     *    unchanged (Slim resolves class-strings via its callable resolver).
     *
     * @param \Slim\App $app
     * @param array<string, RouteEntry> $routeMap
     * @param mixed $gatekeeper middleware to attach to gated routes (e.g. GatekeeperMiddleware)
     * @return void
     */
    public static function addRoutesFromMap($app, array $routeMap, $gatekeeper = null): void
    {
        foreach ($routeMap as $name => $route) {
            // Normalize + expand methods to match historical Slim behavior
            // (a bare 'GET' route also answers HEAD).
            $methods = $route->methods;
            if (is_string($methods) && $methods === 'GET') {
                $methods = ['GET', 'HEAD'];
            } elseif (!is_array($methods)) {
                $methods = [$methods];
            }

            // Build the ordered list of Slim middleware to attach to this route.
            $routeMiddleware = [];
            // Route-specific middleware (framework-agnostic class-strings).
            foreach ($route->middleware as $middleware) {
                if ($middleware === RoleGuard::class && $gatekeeper !== null) {
                    // Present the neutral RoleGuard as the Slim GatekeeperMiddleware
                    // so admin/author-note routes keep their Twig 403 page.
                    $routeMiddleware[] = $gatekeeper;
                } else {
                    $routeMiddleware[] = $middleware;
                }
            }
            // Group-level gatekeeper for gated routes.
            if (!empty($route->gated) && $gatekeeper !== null) {
                $routeMiddleware[] = $gatekeeper;
            }

            $routeObj = $app->map($methods, $route->path, $route->callable);
            if (!empty($name)) {
                $routeObj->setName($name);
            }
            foreach ($routeMiddleware as $middleware) {
                $routeObj = $routeObj->add($middleware);
            }
        }
    }
}
