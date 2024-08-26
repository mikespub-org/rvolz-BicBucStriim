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

use BicBucStriim\AppData\Settings;
use BicBucStriim\Utilities\RequestUtil;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class DefaultMiddleware implements \BicBucStriim\Traits\AppInterface, MiddlewareInterface
{
    use \BicBucStriim\Traits\AppTrait;

    /** @var RequestUtil */
    protected $requester;

    /**
     * Initialize the configuration
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
        $this->setRequester($request);
        return $handler->handle($request);
    }

    /**
     * Set middleware requester (with settings)
     * @param Request $request
     * @param ?Settings $settings
     * @return RequestUtil
     */
    public function setRequester($request, $settings = null)
    {
        $settings ??= $this->settings();
        $this->requester = new RequestUtil($request, $settings);
        return $this->requester;
    }
}
