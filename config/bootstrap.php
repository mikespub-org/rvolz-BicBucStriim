<?php
/**
 * BicBucStriim bootstrap
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

use Psr\Cache\CacheItemPoolInterface;
use Slim\Factory\AppFactory;
use Slim\Handlers\Strategies\RequestResponseArgs;

define('REDBEAN_MODEL_PREFIX', '\\BicBucStriim\\AppData\\Model_');

# The session gc lifetime needs to be at least as high as the Aura.Auth idle ttl, which defaults to 3600
ini_set('session.gc_maxlifetime', 3600);
# Running slim/slim 2.x on PHP 8.2 needs php error_reporting set to E_ALL & ~E_DEPRECATED & ~E_STRICT (= production default)
//error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

# Get app settings
$settings = require(__DIR__ . '/settings.php');

# Get container
$container = require(__DIR__ . '/container.php');

// Set container to create App with on AppFactory
AppFactory::setContainer($container);

# Init app
$app = AppFactory::create();
if (!empty($settings['basepath'])) {
    $app->setBasePath($settings['basepath']);
}

# Configure app for mode
if ($app->getContainer()->has('mode')) {
    $settings['mode'] = $app->getContainer()->get('mode');
}
$config = require(__DIR__ . '/config.php');
$config($app, $settings);

# Store $globalSettings in app config
$app->getContainer()->set('globalSettings', $settings['globalSettings']);

/**
 * See https://www.slimframework.com/docs/v4/objects/routing.html#route-strategies
 * Changing the default invocation strategy on the RouteCollector component
 * will change it for every route being defined after this change being applied
 */
$routeCollector = $app->getRouteCollector();
$routeCollector->setDefaultInvocationStrategy(new RequestResponseArgs());

# Init middleware
$middleware = require(__DIR__ . '/middleware.php');
$middleware($app, $settings);

# Last in first out
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
# We don't really care if someone hits the cache after being logged out
$cachePool = $app->getContainer()->get(CacheItemPoolInterface::class);
$app->add(new \BicBucStriim\Middleware\CachingMiddleware($app, ['/admin', '/login'], $cachePool));
if (!isset($settings['basepath'])) {
    $app->add(new \BicBucStriim\Middleware\BasePathMiddleware($app));
}

//$app->add(ExceptionMiddleware::class);
$app->addErrorMiddleware(true, true, true);

# Init routes
$routes = require(__DIR__ . '/routes.php');
$routes($app, $settings);

return $app;