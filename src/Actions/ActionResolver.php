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
     */
    public function __construct(ActionRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Dispatch a route handler.
     *
     * Accepts either the array form [ActionClass::class, 'methodName'] (the
     * production shape) or a Closure. The array form is resolved through the
     * registry: the singleton action instance is fetched and initialize()d with
     * the request/response (wiring requester/responder/settings via AppTrait),
     * then the named method is called with the route arguments.
     *
     * A Closure is a context-free handler: it has no action instance and is
     * therefore NOT initialize()d, so it cannot use settings()/renderer()/
     * calibre() (those live on the action via AppTrait). It is invoked directly
     * with the request, response and route arguments. Use a small action class
     * instead if a handler needs that app context.
     *
     * @param array{0: class-string, 1: string}|\Closure $callable
     * @param Request $request
     * @param ?Response $response
     * @param array<mixed> $args
     * @return Response|mixed
     */
    public function resolve(array|\Closure $callable, $request, $response = null, array $args = []): mixed
    {
        if ($callable instanceof \Closure) {
            // Context-free handler: no action instance to initialize.
            return $callable($request, $response, ...$args);
        }

        [$class, $method] = $callable;

        // Use the registry to get the singleton instance
        $instance = $this->registry->getInstance($class);

        // Initialize the instance with the request and response
        $instance->initialize($request, $response);

        // Call the method on the instance with the arguments
        return $instance->$method(...$args);
    }
}
