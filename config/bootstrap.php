<?php
/**
 * BicBucStriim bootstrap
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

define('REDBEAN_MODEL_PREFIX', '\\BicBucStriim\\AppData\\Model_');

# The session gc lifetime needs to be at least as high as the Aura.Auth idle ttl, which defaults to 3600
ini_set('session.gc_maxlifetime', 3600);
# Running slim/slim 2.x on PHP 8.2 needs php error_reporting set to E_ALL & ~E_DEPRECATED & ~E_STRICT (= production default)
//error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

# Get app settings
$settings = require(__DIR__ . '/settings.php');

# Init app
$app = new \BicBucStriim\App([
    'view' => new \BicBucStriim\TwigView(),
    'mode' => 'production',
    #'mode' => 'debug',
    #'mode' => 'development',
]);

# Configure app for mode
$config = require(__DIR__ . '/config.php');
$config($app, $settings);

# Store $globalSettings in app config
$app->config('globalSettings', $settings['globalSettings']);

# Freeze (true) DB schema before release! Set to false for DB development.
$app->getContainer()->set('bbs', new \BicBucStriim\AppData\BicBucStriim('data/data.db', true));

# Init middleware
$middleware = require(__DIR__ . '/middleware.php');
$middleware($app, $settings);

# Init routes
$routes = require(__DIR__ . '/routes.php');
$routes($app, $settings);

return $app;
