<?php

/**
 * BicBucStriim middleware
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Middleware;

if (!function_exists('\BicBucStriim\Middleware\getMiddlewareInstances')) {
    function getMiddlewareInstances($container, $settings)
    {
        return [
            new CalibreConfigMiddleware($container),
            new LoginMiddleware($container, $settings['appname'], ['js', 'img', 'style', 'static']),
            new OwnConfigMiddleware($container, $settings['knownConfigs']),
        ];
    }
}

return function ($app, $settings) {
    $middlewares = getMiddlewareInstances($app->getContainer(), $settings);
    foreach ($middlewares as $middleware) {
        $app->add($middleware);
    }
};
