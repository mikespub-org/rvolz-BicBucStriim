<?php

namespace BicBucStriim\Utilities;

/**
 * Class RouteUtil provides route utilities for callables and middleware
 */
class RouteUtil
{
    /**
     * Map routes for action handlers (generic)
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
     * Map routes for action handlers (Slim 4 syntax)
     * @param \Slim\App|\Slim\Routing\RouteCollectorProxy|object $group
     * @param array<mixed> $routes array of name => [method(s), path, ...middleware(s), callable] for each action
     * @param mixed $gatekeeper middleware to call before each route (e.g. GatekeeperMiddleware)
     * @return void
     */
    public static function mapSlim4Routes($group, $routes, $gatekeeper = null)
    {
        foreach ($routes as $name => $routeInfo) {
            $method = array_shift($routeInfo);
            $path = array_shift($routeInfo);
            $callable = array_pop($routeInfo);
            self::addSlim4Route($group, $name, $method, $path, $callable, $routeInfo, $gatekeeper);
        }
    }

    /**
     * Add route to group (Slim 4 syntax)
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
}
