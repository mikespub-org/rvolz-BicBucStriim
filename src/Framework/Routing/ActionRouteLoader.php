<?php

declare(strict_types=1);

namespace BicBucStriim\Framework\Routing;

use BicBucStriim\Actions\ActionRegistry;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * A custom Symfony Route Loader that uses the ActionRegistry to discover routes.
 * @suppress PHP0413
 */
class ActionRouteLoader extends Loader
{
    private bool $isLoaded = false;

    public function __construct(private ActionRegistry $registry)
    {
        parent::__construct();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        if ($this->isLoaded) {
            throw new \RuntimeException('Do not add the "bbs_routes" loader twice');
        }

        $routes = new RouteCollection();
        $routeMap = $this->registry->getRouteMap();

        foreach ($routeMap as $name => $route) {
            // Structured route entry from the ActionRegistry:
            // methods, path, callable, middleware, gated.
            // The callable is either [ActionClass::class, 'methodName'] — stringified
            // to "Class::method" so Symfony instantiates the action and
            // ActionInitializeListener can initialize + middleware-wrap it — or a
            // context-free Closure, passed to _controller as-is (ActionInitialize-
            // Listener runs it through the same PSR-15 pipeline with a PSR-7 request).
            if ($route->callable instanceof \Closure) {
                $controller = $route->callable;
            } else {
                $controller = $route->callable[0] . '::' . $route->callable[1];
            }

            // Symfony has no native per-route middleware, so we carry our
            // route-level middleware and the group gatekeeper flag as route
            // defaults. The ActionInitializeListener reads these and wraps the
            // controller in the appropriate PSR-15 middleware before it runs.
            $defaults = [
                '_controller' => $controller,
                '_bbs_middleware' => $route->middleware,
                '_bbs_gated' => $route->gated,
            ];

            $route_ = new Route($route->path, $defaults);
            $route_->setMethods($route->methods);

            $routes->add($name, $route_);
        }

        $this->isLoaded = true;
        return $routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'bbs_routes' === $type;
    }
}
