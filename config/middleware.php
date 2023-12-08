<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

return function ($app, $settings) {
    # Freeze (true) DB schema before release! Set to false for DB development.
    $app->bbs = new \BicBucStriim\AppData\BicBucStriim('data/data.db', true);
    $app->add(new \BicBucStriim\Middleware\CalibreConfigMiddleware(CALIBRE_DIR));
    $app->add(new \BicBucStriim\Middleware\LoginMiddleware($settings['appname'], ['js', 'img', 'style']));
    $app->add(new \BicBucStriim\Middleware\OwnConfigMiddleware($settings['knownConfigs']));
    $app->add(new \BicBucStriim\Middleware\CachingMiddleware(['/admin', '/login']));
};
