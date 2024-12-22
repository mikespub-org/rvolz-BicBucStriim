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
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/*********************************************************************
 * Action resolver (framework agnostic)
 ********************************************************************/
class ActionResolver
{
    /** @var ActionRegistry */
    private $registry;
    /** @var ?Container */
    private $container;
    /** @var array<class-string, DefaultActions> */
    private $instances = [];

    /**
     * Summary of __construct
     * @param ActionRegistry $registry
     * @param ?Container $container
     */
    public function __construct(ActionRegistry $registry, Container $container = null)
    {
        $this->registry = $registry;
        $this->container = $container;
    }

    /**
     * Summary of resolve
     * @param class-string $class
     * @param string $method
     * @param Request $request
     * @param ?Response $response
     * @param array<mixed> $args
     * @return Response|mixed
     */
    public function resolve($class, $method, $request, $response = null, array $args = []): mixed
    {
        // Use the registry to get the instance
        //$instance = $this->registry->getInstance($class);
        $instance = $this->instances[$class] ?? $this->instances[$class] = $this->createInstance($class);

        // Initialize the instance with the request and response
        $instance->initialize($request, $response);

        // Call the method on the instance with the arguments
        return $instance->$method(...$args);
    }

    /**
     * Summary of createInstance
     * @param class-string $class
     * @return DefaultActions
     */
    public function createInstance($class): object
    {
        // Single creation point for better control - @todo: add dependency injection
        return new $class($this->container);
    }
}
