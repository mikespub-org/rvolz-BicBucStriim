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

    /**
     * Summary of __construct
     * @param ActionRegistry $registry
     * @param ?Container $container
     */
    public function __construct(ActionRegistry $registry, Container $container = null)
    {
        $this->registry = $registry;
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
        // Use the registry to get the singleton instance
        $instance = $this->registry->getInstance($class);

        // Initialize the instance with the request and response
        $instance->initialize($request, $response);

        // Call the method on the instance with the arguments
        return $instance->$method(...$args);
    }
}
