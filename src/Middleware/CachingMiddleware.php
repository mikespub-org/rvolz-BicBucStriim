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

use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Middlewares\Cache as CacheMiddleware;

class CachingMiddleware extends CacheMiddleware
{
    use \BicBucStriim\Traits\AppTrait;

    /** @var \BicBucStriim\App|\Slim\App|object */
    protected $app;
    protected $resources;

    /**
     * Initialize the configuration
     *
     * @param \BicBucStriim\App|\Slim\App|object $app The app
     * @param array $config an array of resource strings
     * @param CacheItemPoolInterface $cachePool the cache item pool for the cache middleware
     */
    public function __construct($app, $config, $cachePool)
    {
        $this->app = $app;
        $this->resources = $config;
        parent::__construct($cachePool, $app->getResponseFactory());
    }

    /**
     * If the current resource belongs to the admin area caching will be disabled.
     *
     * This call must happen before own_config_middleware, because there the PHP
     * session will be started, and cache-control must happen before that.
     * @param Request $request The request
     * @param RequestHandler $handler The handler
     * @return Response The response
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $this->request = $request;
        //$response = $this->response();
        $resource = $this->getResourceUri();
        foreach ($this->resources as $noCacheResource) {
            if (str_starts_with($resource, $noCacheResource)) {
                session_cache_limiter('nocache');
                $this->log()->debug('caching_middleware: caching disabled for ' . $resource);
                break;
            }
        }
        return parent::process($request, $handler);
    }
}
