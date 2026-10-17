<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Actions;

use Psr\Container\ContainerInterface as Container;
use BicBucStriim\Utilities\RouteEntry;
use BicBucStriim\Utilities\RouteUtil;

/*********************************************************************
 * Action registry (framework agnostic)
 ********************************************************************/
class ActionRegistry
{
    /** @var array<class-string> */
    private $actions = [];
    /** @var array<class-string, string|null> */
    private $prefixes = [];
    /** @var array<class-string, DefaultActions> */
    private $instances;
    /** @var ?Container */
    private $container;
    /** @var array<string, RouteEntry> */
    private $routeMap = [];

    /**
     * Summary of __construct
     * @param ?Container $container
     */
    public function __construct(?Container $container = null)
    {
        $this->container = $container;
        $this->instances = [];
    }

    /**
     * Summary of register
     * @param class-string $actionClass
     * @param ?string $prefix optional URL prefix to mount the class' routes
     *   under; defaults to the class' own PREFIX constant.
     * @return void
     */
    public function register($actionClass, $prefix = null)
    {
        // Store action class only if not already present to avoid duplicates
        if (!in_array($actionClass, $this->actions, true)) {
            $this->actions[] = $actionClass;
            // Use the class' own PREFIX constant when no prefix is given
            $this->prefixes[$actionClass] = $prefix ?? (defined("$actionClass::PREFIX") ? $actionClass::PREFIX : null);
        }
    }

    /**
     * Summary of getInstance
     * @param class-string $actionClass
     * @return DefaultActions
     */
    public function getInstance($actionClass): object
    {
        if (!isset($this->instances[$actionClass])) {
            // Wrap the real container in our adapter to provide a stable interface
            $containerAdapter = new \BicBucStriim\Framework\ContainerAdapter($this->container);
            // Pass the registry itself as an optional second argument. PHP
            // silently ignores extra positional args for the (single-argument)
            // action constructors, while ApiActions uses it to introspect the
            // shared, fully-populated route map without depending on Slim's
            // RouteCollectorInterface.
            $this->instances[$actionClass] = new $actionClass($containerAdapter, $this);
        }
        return $this->instances[$actionClass];
    }

    /**
     * Summary of getRouteMap
     *
     * Returns the framework-agnostic route map as an array of RouteEntry
     * objects keyed by route name. Each entry carries: methods, path,
     * callable, middleware and gated (see loadRoutes()).
     *
     * @return array<string, RouteEntry>
     */
    public function getRouteMap(): array
    {
        if (!$this->routeMap) {
            $this->loadRoutes();
        }
        return $this->routeMap;
    }

    /**
     * Summary of loadRoutes
     * @return void
     */
    protected function loadRoutes(): void
    {
        // Load routes only when needed
        foreach ($this->getActionClasses() as $actionClass) {
            if (!method_exists($actionClass, 'getRoutes')) {
                continue;
            }
            // Use the prefix provided at register() time (falls back to the
            // class' own PREFIX constant), so e.g. DefaultActions can be
            // mounted under /api even though its PREFIX is null.
            $prefix = $this->prefixes[$actionClass] ?? null;
            // Group-level gatekeeping policy, shared by all framework adapters.
            $gated = method_exists($actionClass, 'usesGroupGatekeeper') && $actionClass::usesGroupGatekeeper();

            $routes = $actionClass::getRoutes($actionClass);
            foreach ($routes as $name => $routeInfo) {
                $this->addRouteToMap($name, $routeInfo, $prefix, $gated);
            }

            // Extra routes (e.g. the API CORS preflight) are registered at the
            // application root, i.e. *without* the action's URL prefix, and are
            // never gated.
            if (method_exists($actionClass, 'getExtraRoutes')) {
                foreach ($actionClass::getExtraRoutes($actionClass) as $name => $routeInfo) {
                    $this->addRouteToMap($name, $routeInfo, null, false);
                }
            }
        }
    }

    /**
     * Build a single route-map entry from a route tuple.
     *
     * This is the single place that shapes a route-map entry (methods, path,
     * callable, middleware, gated), shared by both the class' own routes and
     * its extra (root-level) routes. Callers control the two things that
     * actually differ between the two: whether a URL prefix is applied and
     * whether the route is gated.
     *
     * @param string $name
     * @param array<mixed> $routeInfo a route tuple: method(s), path, ...middleware(s), callable
     * @param ?string $prefix optional URL prefix to prepend to the path
     * @param bool $gated whether the route requires the group-level role guard
     * @return void
     */
    private function addRouteToMap(string $name, array $routeInfo, ?string $prefix, bool $gated): void
    {
        $parsed = RouteUtil::parseRouteTuple($routeInfo);
        $path = $parsed['path'];
        if (!empty($prefix)) {
            // Prepend the action's prefix to the route path
            $path = rtrim($prefix, '/') . $path;
        }
        $this->routeMap[$name] = new RouteEntry(
            name: $name,
            methods: $parsed['methods'],
            path: $path,
            callable: $parsed['callable'],
            middleware: array_values(array_filter($parsed['middleware'])),
            gated: $gated,
        );
    }

    /**
     * Summary of getActionClasses
     * @return array<class-string>
     */
    public function getActionClasses(): array
    {
        return $this->actions;
    }
}
