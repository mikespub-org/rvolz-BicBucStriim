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
    /** @var \BicBucStriim\App|\Slim\App|object */
    protected $app;

    /**
     * If we accept callable of format [static::class, 'method'] too, we need app
     * @param \BicBucStriim\App|\Slim\App|object $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

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
            // when using [static::class, 'method'] - doesn't help with middleware
            if (is_string($class) && is_a($class, DefaultActions::class, true)) {
                $class = new $class($this->app);
            }
            // when using [$self, 'method']
            if (is_object($class) && $class instanceof DefaultActions) {
                $class->request($request);
                $class->response($response);
                $callable(...array_values($routeArguments));
                return $class->response();
            }
        }
        return $callable($request, $response, ...array_values($routeArguments));
    }
}
