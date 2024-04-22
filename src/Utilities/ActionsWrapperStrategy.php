<?php

namespace BicBucStriim\Utilities;

use BicBucStriim\Actions\DefaultActions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;

/**
 * Wrapper for actions methods accepting only route arguments as parameters (slim 2 style)
 */
class ActionsWrapperStrategy implements InvocationStrategyInterface
{
    /**
     * Use callable of format [$self, 'method'] as route handler (slim 2 style)
     * and wrap request/response handling on actions instance level
     *
     * @param array<string, string>  $routeArguments
     */
    public function __invoke(
        callable|array $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {
        if (is_array($callable) && $callable[0] instanceof DefaultActions) {
            $callable[0]->request($request);
            $callable[0]->response($response);
            $callable(...array_values($routeArguments));
            return $callable[0]->response();
        }
        return $callable($request, $response, ...array_values($routeArguments));
    }
}
