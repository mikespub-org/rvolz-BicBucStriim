<?php
/**
 * BicBucStriim middleware
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim;

function getMiddlewareInstances($settings)
{
    return [
        new Middleware\CalibreConfigMiddleware(CALIBRE_DIR),
        new Middleware\LoginMiddleware($settings['appname'], ['js', 'img', 'style']),
        new Middleware\OwnConfigMiddleware($settings['knownConfigs']),
        new Middleware\CachingMiddleware(['/admin', '/login']),
    ];
}

return function ($app, $settings) {
    $middlewares = getMiddlewareInstances($settings);
    foreach ($middlewares as $middleware) {
        $app->add($middleware);
    }
};
