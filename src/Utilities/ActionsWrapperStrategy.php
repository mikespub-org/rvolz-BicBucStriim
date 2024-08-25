<?php

namespace BicBucStriim\Utilities;

use BicBucStriim\Actions\DefaultActions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;

/**
 * Wrapper for actions methods accepting only route arguments as parameters (slim 2 style)
 * @see \Slim\Handlers\Strategies\RequestResponseArgs
 */
class ActionsWrapperStrategy implements InvocationStrategyInterface
{
    /**
     * Use callable of format [$self, 'method'] as route handler (slim 2 style)
     * and wrap request/response handling on actions instance level
     * @param array<string, string>  $routeArguments
     */
    public function __invoke(
        callable|array $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {
        if (is_array($callable)) {
            [$class, $method] = $callable;
            // when using [static::class, 'method']
            // ... we never get this because Slim goes through CallableResolver first
            // when using [$self, 'method']
            if (is_object($class) && $class instanceof DefaultActions) {
                // set initial request and response in actions instance
                $class->initialize($request, $response);
                // callable can return void (old-style) or response (new-style)
                $result = $callable(...array_values($routeArguments));
                //$result ??= $class->response();
                return $result;
            }
        }
        return $callable($request, $response, ...array_values($routeArguments));
    }
}
