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

/*********************************************************************
 * Action registry (framework agnostic)
 ********************************************************************/
class ActionRegistry
{
    /** @var array<class-string> */
    private $actions = [];
    /** @var array<class-string, DefaultActions> */
    private $instances = [];
    private $routeMap = [];

    /**
     * Summary of register
     * @param class-string $actionClass
     * @return void
     */
    public function register($actionClass)
    {
        // Store action class
        $this->actions[] = $actionClass;
    }

    /**
     * Summary of getInstance
     * @param class-string $actionClass
     * @return DefaultActions
     */
    public function getInstance($actionClass): object
    {
        if (!isset($this->instances[$actionClass])) {
            $this->instances[$actionClass] = new $actionClass();
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
            if (method_exists($actionClass, 'getRoutes')) {
                // @todo handle $gatekeeper
                $this->routeMap = array_merge(
                    $this->routeMap,
                    $actionClass::getRoutes($actionClass)
                );
            }
        }
    }

    /**
     * Summary of getActionClasses
     * @return array<class-string>
     */
    public function getActionClasses(): array
    {
        //return $this->actions;
        // Could be cached in production
        return [
            MainActions::class,
            AdminActions::class,
            MetadataActions::class,
            OpdsActions::class,
            ExtraActions::class,
        ];
    }
}
