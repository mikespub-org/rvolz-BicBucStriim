<?php

namespace BicBucStriim\Utilities;

use BicBucStriim\Framework\RoleGuard;

/**
 * Class RouteUtil provides route utilities for callables and middleware
 */
class RouteUtil
{
    /**
     * Map routes for action handlers (generic)
     *
     * @deprecated 4.1.5 use RouteUtil::addRoutesFromMap() instead.
     * @param object $group
     * @param array<mixed> $routes array of name => [method(s), path, ...middleware(s), callable] for each action
     * @param mixed $gatekeeper middleware to call before each route (e.g. GatekeeperMiddleware)
     * @return void
     */
    public static function mapRoutes($group, $routes, $gatekeeper = null)
    {
        self::mapSlim4Routes($group, $routes, $gatekeeper);
    }

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
     * @return array{methods: array<string>|string, path: string, callable: callable, middleware: array<int, mixed>}
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
     * Map routes for action handlers (Slim 4 syntax)
     *
     * @deprecated 4.1.5 use RouteUtil::addRoutesFromMap() instead.
     * @param \Slim\App|\Slim\Routing\RouteCollectorProxy|object $group
     * @param array<mixed> $routes array of name => [method(s), path, ...middleware(s), callable] for each action
     * @param mixed $gatekeeper middleware to call before each route (e.g. GatekeeperMiddleware)
     * @return void
     */
    public static function mapSlim4Routes($group, $routes, $gatekeeper = null)
    {
        foreach ($routes as $name => $routeInfo) {
            $parsed = self::parseRouteTuple($routeInfo);
            self::addSlim4Route($group, $name, $parsed['methods'], $parsed['path'], $parsed['callable'], $parsed['middleware'], $gatekeeper);
        }
    }

    /**
     * Add all routes for an action class to a Slim route collector.
     *
     * This is the single, Slim-specific counterpart to the framework-agnostic
     * route definitions on the action classes (getRoutes()/getExtraRoutes()).
     * It mounts the class' routes under the given prefix, applies the group
     * middleware ($gatekeeper) when the class requires admin access, and
     * registers any root-level extra routes (e.g. the API CORS preflight)
     * without a prefix. Keeping this here (instead of on the Action classes)
     * keeps src/Actions free of any Slim dependency.
     *
     * @deprecated 4.1.5 use RouteUtil::addRoutesFromMap() instead.
     * @param \Slim\App|\Slim\Routing\RouteCollectorProxy|object $app
     * @param class-string $class action class defining getRoutes()/getExtraRoutes()
     * @param ?string $prefix
     * @param mixed $gatekeeper middleware to apply to the group (optional)
     * @return void
     */
    public static function addActionRoutes($app, $class, $prefix = null, $gatekeeper = null): void
    {
        // Slim 4 framework uses its own CallableResolver if this is a class string, *before* invocation strategy
        $prefix ??= defined("$class::PREFIX") ? $class::PREFIX : null;
        $routes = $class::getRoutes($class, $gatekeeper);
        if (!empty($prefix)) {
            // The group's ->add() returns the (mutable) route collection for the
            // group, which also applies the group URL prefix to each route
            // pattern. We always resolve it (attaching the group middleware
            // $gatekeeper when this class requires admin access) so the routes
            // are finalized the same way regardless of the middleware.
            $group = $app->group($prefix, function (\Slim\Routing\RouteCollectorProxy $group) use ($routes) {
                self::mapRoutes($group, $routes);
            });
            if ($gatekeeper && $class::usesGroupGatekeeper()) {
                $group->add($gatekeeper);
            }
        } else {
            self::mapRoutes($app, $routes);
        }
        // Root-level extra routes (e.g. API CORS preflight) - never prefixed
        $extraRoutes = $class::getExtraRoutes($class, $gatekeeper);
        if (!empty($extraRoutes)) {
            self::mapRoutes($app, $extraRoutes);
        }
    }

    /**
     * Add route to group (Slim 4 syntax)
     *
     * @deprecated 4.1.5 use RouteUtil::addRoutesFromMap() instead.
     * @param \Slim\App|\Slim\Routing\RouteCollectorProxy|object $group
     * @param string $name
     * @param array<string>|string $method
     * @param string $path
     * @param callable $callable of format [$self, 'method']
     * @param array<mixed> $middlewareList list of middleware(s) (*not* callable of format [$self, 'method'])
     * @param mixed $gatekeeper middleware to call before each route (e.g. GatekeeperMiddleware)
     */
    public static function addSlim4Route($group, $name, $method, $path, $callable, $middlewareList = [], $gatekeeper = null)
    {
        if (is_string($method) && $method === 'GET') {
            $method = ['GET', 'HEAD'];
        }
        if (!is_array($method)) {
            $method = [ $method ];
        }
        //$group->map($path, ...$routeInfo)->via($method);
        // See also https://www.slimframework.com/docs/v4/objects/routing.html#route-strategies
        // For example from RequestResponseArgs.php:
        //return $callable($request, $response, ...array_values($routeArguments));
        // Note: if we only have one middleware in the list for this route, it will act as gatekeeper too
        // See ['GET', '/authors/{id}/notes/', $gatekeeper, [$self, 'authorNotes']] in MainActions
        if (empty($gatekeeper) && !empty($middlewareList) && count($middlewareList) == 1) {
            $gatekeeper = array_shift($middlewareList);
        }
        if ($gatekeeper) {
            //$wrapper = self::wrapRequestMiddleware($gatekeeper);
            $route = $group->map($method, $path, $callable)->add($gatekeeper);
        } else {
            $route = $group->map($method, $path, $callable);
        }
        if (!empty($name)) {
            $route->setName($name);
        }
        if (empty($middlewareList)) {
            return;
        }
        foreach ($middlewareList as $middleware) {
            //$wrapper = self::wrapRequestMiddleware($middleware);
            $route = $route->add($middleware);
        }
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
     * @param array<string, array{methods: array<string>|string, path: string, callable: callable, middleware: array<int, string|object>, gated: bool}> $routeMap
     * @param mixed $gatekeeper middleware to attach to gated routes (e.g. GatekeeperMiddleware)
     * @return void
     */
    public static function addRoutesFromMap($app, array $routeMap, $gatekeeper = null): void
    {
        foreach ($routeMap as $name => $route) {
            // Normalize + expand methods to match historical Slim behavior
            // (a bare 'GET' route also answers HEAD).
            $methods = $route['methods'];
            if (is_string($methods) && $methods === 'GET') {
                $methods = ['GET', 'HEAD'];
            } elseif (!is_array($methods)) {
                $methods = [$methods];
            }

            // Build the ordered list of Slim middleware to attach to this route.
            $routeMiddleware = [];
            // Route-specific middleware (framework-agnostic class-strings).
            foreach ($route['middleware'] as $middleware) {
                if ($middleware === RoleGuard::class && $gatekeeper !== null) {
                    // Present the neutral RoleGuard as the Slim GatekeeperMiddleware
                    // so admin/author-note routes keep their Twig 403 page.
                    $routeMiddleware[] = $gatekeeper;
                } else {
                    $routeMiddleware[] = $middleware;
                }
            }
            // Group-level gatekeeper for gated routes.
            if (!empty($route['gated']) && $gatekeeper !== null) {
                $routeMiddleware[] = $gatekeeper;
            }

            $routeObj = $app->map($methods, $route['path'], $route['callable']);
            if (!empty($name)) {
                $routeObj->setName($name);
            }
            foreach ($routeMiddleware as $middleware) {
                $routeObj = $routeObj->add($middleware);
            }
        }
    }
}
