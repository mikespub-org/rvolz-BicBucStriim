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

class CachingMiddleware extends DefaultMiddleware
{
    protected $resources;

    /**
     * Initialize the configuration
     *
     * @param array $config an array of resource strings
     */
    public function __construct($config)
    {
        $this->resources = $config;
    }
    /**
     * If the current resource belongs to the admin area caching will be disabled.
     *
     * This call must happen before own_config_middleware, because there the PHP
     * session will be started, and cache-control must happen before that.
     */
    public function call()
    {
        $request = $this->request();
        $resource = $request->getResourceUri();
        foreach ($this->resources as $noCacheResource) {
            if (str_starts_with($resource, $noCacheResource)) {
                session_cache_limiter('nocache');
                $this->log()->debug('caching_middleware: caching disabled for ' . $resource);
                break;
            }
        }
        $this->next->call();
    }
}
