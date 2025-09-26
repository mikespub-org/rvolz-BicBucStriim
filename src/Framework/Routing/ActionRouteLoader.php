<?php

declare(strict_types=1);

namespace BicBucStriim\Framework\Routing;

use BicBucStriim\Actions\ActionRegistry;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * A custom Symfony Route Loader that uses the ActionRegistry to discover routes.
 */
class ActionRouteLoader extends Loader
{
    private bool $isLoaded = false;

    public function __construct(private ActionRegistry $registry)
    {
        parent::__construct();
    }

    public function load(mixed $resource, string $type = null): RouteCollection
    {
        if ($this->isLoaded) {
            throw new \RuntimeException('Do not add the "bbs_routes" loader twice');
        }

        $routes = new RouteCollection();
        $routeMap = $this->registry->getRouteMap();

        foreach ($routeMap as $name => $routeInfo) {
            // $routeInfo is [$methods, $path, $callable]
            // $callable is [ActionClass::class, 'methodName']
            $controller = $routeInfo[2][0] . '::' . $routeInfo[2][1];

            $route = new Route($routeInfo[1], ['_controller' => $controller]);
            $route->setMethods($routeInfo[0]);

            $routes->add($name, $route);
        }

        $this->isLoaded = true;
        return $routes;
    }

    public function supports(mixed $resource, string $type = null): bool
    {
        return 'bbs_routes' === $type;
    }
}
