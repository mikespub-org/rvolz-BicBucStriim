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
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Factory\AppFactory;
use BicBucStriim\AppData\Settings;
use BicBucStriim\Utilities\ActionsWrapperStrategy;
use Slim\Interfaces\RouteCollectorInterface;

define('REDBEAN_MODEL_PREFIX', '\\BicBucStriim\\Models\\');

# The session gc lifetime needs to be at least as high as the Aura.Auth idle ttl, which defaults to 3600
ini_set('session.gc_maxlifetime', 3600);
# Set php error_reporting to production default in config.php
//error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

# Get app settings
$settings = require(__DIR__ . '/settings.php');

# Get container
$container = require(__DIR__ . '/container.php');

// Set container to create App with on AppFactory
AppFactory::setContainer($container);

# Init app
$app = AppFactory::create();
# Base path - null means undefined, empty '' or '/bbs' etc. mean predefined
if (isset($settings['basepath'])) {
    $app->setBasePath($settings['basepath']);
}
// Set app in callable resolver to instantiate string actions
//$callableResolver->setApp($app);

# Configure app for mode
$config = require(__DIR__ . '/config.php');
$config($app, $settings);

# Store $globalSettings in container for everything
$app->getContainer()->set(Settings::class, $settings['globalSettings']);

# Store responseFactory in container for middleware response
$app->getContainer()->set(ResponseFactoryInterface::class, fn() => $app->getResponseFactory());

# Store routeCollector in container for api routes
$app->getContainer()->set(RouteCollectorInterface::class, fn() => $app->getRouteCollector());

/**
 * See https://www.slimframework.com/docs/v4/objects/routing.html#route-strategies
 * Changing the default invocation strategy on the RouteCollector component
 * will change it for every route being defined after this change being applied
 */
$routeCollector = $app->getRouteCollector();
$routeCollector->setDefaultInvocationStrategy(new ActionsWrapperStrategy());

# Init middleware
$middleware = require(__DIR__ . '/middleware.php');
$middleware($app, $settings);

# Last in first out
$app->addBodyParsingMiddleware();

# We don't really care if someone hits the cache after being logged out
$cachePool = $app->getContainer()->get(CacheItemPoolInterface::class);
$responseFactory = $app->getResponseFactory();
$app->add(new \BicBucStriim\Middleware\CachingMiddleware($app->getContainer(), ['/admin', '/login', '/api'], $cachePool, $responseFactory));

$app->addRoutingMiddleware();
# Base path - null means undefined, empty '' or '/bbs' etc. mean predefined
if (!isset($settings['basepath'])) {
    $app->add(new \BicBucStriim\Middleware\BasePathMiddleware($app));
}

//$app->add(ExceptionMiddleware::class);
$settings['mode'] ??= 'production';
if ($settings['mode'] == 'production') {
    $displayErrorDetails = false;
} else {
    $displayErrorDetails = true;
}
$app->addErrorMiddleware($displayErrorDetails, true, true);

# Use gatekeeper middleware in routes
$gatekeeper = new \BicBucStriim\Middleware\GatekeeperMiddleware($app->getContainer());

# Init routes
$routes = require(__DIR__ . '/routes.php');
$routes($app, $settings, $gatekeeper);

return $app;
