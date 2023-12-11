<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class DefaultMiddleware implements \BicBucStriim\Traits\AppInterface, MiddlewareInterface
{
    use \BicBucStriim\Traits\AppTrait;

    /** @var \BicBucStriim\App|\Slim\App|object */
    protected $app;

    /**
     * Initialize the configuration
     *
     * @param \BicBucStriim\App|\Slim\App|object $app The app
     */
    public function __construct($app)
    {
        $this->app($app);
    }

    /**
     * Invoke middleware.
     *
     * @param Request $request The request
     * @param RequestHandler $handler The handler
     * @return Response The response
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $this->request = $request;
        //$response = $this->response();
        return $handler->handle($request);
    }
}
