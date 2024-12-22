<?php

/**
 * BicBucStriim config
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

/**
 * Load the right config.{mode}.php file
 */
return function ($app, $settings) {
    $settings['mode'] ??= 'production';
    $configfunc = match ($settings['mode']) {
        'development' => require(__DIR__ . '/config.dev.php'),
        'debug' => require(__DIR__ . '/config.debug.php'),
        default => require(__DIR__ . '/config.prod.php'),
    };
    $configfunc($app, $settings['appname'], $settings['appversion']);
};
