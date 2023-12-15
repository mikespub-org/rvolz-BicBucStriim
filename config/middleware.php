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

function getMiddlewareInstances($app, $settings)
{
    return [
        new Middleware\CalibreConfigMiddleware($app, CALIBRE_DIR),
        new Middleware\LoginMiddleware($app, $settings['appname'], ['js', 'img', 'style', 'static']),
        new Middleware\OwnConfigMiddleware($app, $settings['knownConfigs']),
    ];
}

return function ($app, $settings) {
    $middlewares = getMiddlewareInstances($app, $settings);
    foreach ($middlewares as $middleware) {
        $app->add($middleware);
    }
};
