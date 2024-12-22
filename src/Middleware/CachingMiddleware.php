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

use BicBucStriim\Utilities\RequestUtil;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Middlewares\Cache as CacheMiddleware;
use Middlewares\CachePrevention;

class CachingMiddleware extends CacheMiddleware
{
    use \BicBucStriim\Traits\AppTrait;

    /** @var array<string> */
    protected $resources;

    /**
     * Initialize the configuration
     *
     * @param array<string> $cacheConfig an array of resource strings
     * @param CacheItemPoolInterface $cachePool the cache item pool for the cache middleware
     * @param ResponseFactoryInterface $responseFactory the cache item pool for the cache middleware
     */
    public function __construct(ContainerInterface $container, $cacheConfig, $cachePool, $responseFactory)
    {
        $this->container = $container;
        $this->resources = $cacheConfig;
        parent::__construct($cachePool, $responseFactory);
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
        $requester = new RequestUtil($request);
        $resource = $requester->getPathInfo();
        foreach ($this->resources as $noCacheResource) {
            if (str_starts_with($resource, $noCacheResource)) {
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_cache_limiter('nocache');
                }
                $this->log()->debug('caching_middleware: caching disabled for ' . $resource);
                break;
            }
        }
        // @todo prevent caching for all json API calls?
        //if ($request->hasHeader('Accept') && in_array('application/json', $request->getHeader('Accept'))) {
        //    $prevention = new CachePrevention();
        //    return $prevention->process($request, $handler);
        //}
        return parent::process($request, $handler);
    }
}
