<?php

namespace BicBucStriim\Utilities;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Class RouteUtil provides route utilities for callables and middleware
 */
class RouteUtil
{
    /**
     * Map routes for action handlers (generic)
     * @param object $group
     * @param array<mixed> $routes list of [method(s), path, ...middleware(s), callable] for each action
     * @param mixed $gatekeeper middleware to call before each route (e.g. GatekeeperMiddleware)
     * @return void
     */
    public static function mapRoutes($group, $routes, $gatekeeper = null)
    {
        static::mapSlim4Routes($group, $routes, $gatekeeper);
    }

    /**
     * Map routes for action handlers (Slim 4 syntax)
     * @param \Slim\App|\Slim\Routing\RouteCollectorProxy|object $group
     * @param array<mixed> $routes list of [method(s), path, ...middleware(s), callable] for each action
     * @param mixed $gatekeeper middleware to call before each route (e.g. GatekeeperMiddleware)
     * @return void
     */
    public static function mapSlim4Routes($group, $routes, $gatekeeper = null)
    {
        foreach ($routes as $routeInfo) {
            $method = array_shift($routeInfo);
            $path = array_shift($routeInfo);
            $callable = array_pop($routeInfo);
            static::addSlim4Route($group, $method, $path, $callable, $routeInfo, $gatekeeper);
        }
    }

    /**
     * Add route to group (Slim 4 syntax)
     * @param \Slim\App|\Slim\Routing\RouteCollectorProxy|object $group
     * @param array<string>|string $method
     * @param string $path
     * @param callable $callable of format [$self, 'method']
     * @param array<mixed> $middlewareList list of middleware(s) (*not* callable of format [$self, 'method'])
     * @param mixed $gatekeeper middleware to call before each route (e.g. GatekeeperMiddleware)
     */
    public static function addSlim4Route($group, $method, $path, $callable, $middlewareList = [], $gatekeeper = null)
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
            //$wrapper = static::wrapRequestMiddleware($gatekeeper);
            $route = $group->map($method, $path, $callable)->add($gatekeeper);
        } else {
            $route = $group->map($method, $path, $callable);
        }
        if (empty($middlewareList)) {
            return;
        }
        foreach ($middlewareList as $middleware) {
            //$wrapper = static::wrapRequestMiddleware($middleware);
            $route = $route->add($middleware);
        }
    }

    /**
     * Use callable of format [$self, 'method'] as route handler (with RequestResponseArgs strategy)
     * @param callable|array $callable
     * @deprecated 3.3.0 replaced by using ActionsWrapperStrategy instead
     * @return callable
     */
    public static function wrapRouteHandler($callable)
    {
        return function (Request $request, Response $response, ...$args) use ($callable) {
            $callable[0]->request($request);
            $callable[0]->response($response);
            $callable(...$args);
            return $callable[0]->response();
        };
    }

    /**
     * Use callable of format [$self, 'method'] as route handler (with RequestResponseArgs strategy)
     * @param callable|array $callable
     * @param mixed $gatekeeper of format [$self, 'method'] to call before each route (e.g. check_admin)
     * @deprecated 3.3.0 replaced by using ActionsWrapperStrategy instead
     * @return callable
     */
    public static function wrapGuardedRouteHandler($callable, $gatekeeper)
    {
        return function (Request $request, Response $response, ...$args) use ($callable, $gatekeeper) {
            // invoke gatekeeper first (e.g. check_admin)
            $gatekeeper[0]->request($request);
            $gatekeeper[0]->response($response);
            // return gatekeeper response if it returns true
            if ($gatekeeper(...$args)) {
                return $gatekeeper[0]->response();
            }
            // invoke callable and return its response
            $callable[0]->request($gatekeeper[0]->request());
            $callable[0]->response($gatekeeper[0]->response());
            $callable(...$args);
            return $callable[0]->response();
        };
    }

    /**
     * Use callable of format [$self, 'method'] in "request" middleware (= callable first, then handler)
     * Note: the callable must return true if we have a response ready, false otherwise
     * @param callable|array $callable
     * @deprecated 3.4.0 replaced by using GatekeeperMiddleware instead
     * @return callable
     */
    public static function wrapRequestMiddleware($callable)
    {
        return function (Request $request, RequestHandler $handler) use ($callable) {
            // invoke callable first
            $callable[0]->request($request);
            // return callable response if it returns true
            if ($callable()) {
                return $callable[0]->response();
            }
            // invoke handler last and return its response
            return $handler->handle($callable[0]->request());
        };
    }

    /**
     * Use callable of format [$self, 'method'] in "response" middleware (= handler first, then callable)
     * @param callable|array $callable
     * @deprecated 3.4.0 replaced by using actual middleware instead
     * @return callable
     */
    public static function wrapResponseMiddleware($callable)
    {
        return function (Request $request, RequestHandler $handler) use ($callable) {
            // invoke handler first and get its response
            $response = $handler->handle($request);
            // invoke callable last
            $callable[0]->request($request);
            $callable[0]->response($response);
            $callable();
            // return callable response
            return $callable[0]->response();
        };
    }
}
