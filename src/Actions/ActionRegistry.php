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

/*********************************************************************
 * Action registry (framework agnostic)
 ********************************************************************/
class ActionRegistry
{
    /** @var array<class-string> */
    private $actions = [];
    /** @var array<class-string, DefaultActions> */
    private $instances;
    /** @var ?\Psr\Container\ContainerInterface */
    private $container;
    private $routeMap = [];

    /**
     * Summary of __construct
     * @param ?\Psr\Container\ContainerInterface $container
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container;
        $this->instances = [];
    }

    /**
     * Summary of register
     * @param class-string $actionClass
     * @return void
     */
    public function register($actionClass)
    {
        // Store action class only if not already present to avoid duplicates
        if (!in_array($actionClass, $this->actions, true)) {
            $this->actions[] = $actionClass;
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
            $this->instances[$actionClass] = new $actionClass($containerAdapter);
        }
        return $this->instances[$actionClass];
    }

    /**
     * Summary of getRouteMap
     * @return array<mixed>
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
            // @todo handle $gatekeeper
            $prefix = $actionClass::PREFIX;
            $routes = $actionClass::getRoutes($actionClass);

            foreach ($routes as $name => $routeInfo) {
                if (!empty($prefix)) {
                    // Prepend the action's prefix to the route path
                    $routeInfo[1] = rtrim($prefix, '/') . $routeInfo[1];
                }
                $this->routeMap[$name] = $routeInfo;
            }
        }
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
