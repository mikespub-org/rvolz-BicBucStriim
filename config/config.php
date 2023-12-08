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
 * Configure app for production
 */
function confprod()
{
    global $app, $appname, $appversion;
    $app->config([
        'debug' => false,
        'cookies.lifetime' => '1 day',
        'cookies.secret_key' => 'b4924c3579e2850a6fad8597da7ad24bf43ab78e',

    ]);
    $app->getLog()->setEnabled(true);
    $app->getLog()->setLevel(\Slim\Log::WARN);
    $app->getLog()->info($appname . ' ' . $appversion . ': Running in production mode.');
    $app->getLog()->info('Running on PHP: ' . PHP_VERSION);
    error_reporting(E_ALL ^ (E_DEPRECATED | E_USER_DEPRECATED));
}

/**
 * Configure app for development
 */
function confdev()
{
    global $app, $appname, $appversion;
    $app->config([
        'debug' => true,
        'cookies.lifetime' => '5 minutes',
        'cookies.secret_key' => 'b4924c3579e2850a6fad8597da7ad24bf43ab78e',

    ]);
    $app->getLog()->setEnabled(true);
    $app->getLog()->setLevel(\Slim\Log::DEBUG);
    $app->getLog()->info($appname . ' ' . $appversion . ': Running in development mode.');
    $app->getLog()->info('Running on PHP: ' . PHP_VERSION);
}

/**
 * Configure app for debug mode: production + log everything to file
 */
function confdebug()
{
    global $app, $appname, $appversion;
    $app->config([
        'debug' => true,
        'cookies.lifetime' => '1 day',
        'cookies.secret_key' => 'b4924c3579e2850a6fad8597da7ad24bf43ab78e',
    ]);
    $app->getLog()->setEnabled(true);
    $app->getLog()->setLevel(\Slim\Log::DEBUG);
    $app->getLog()->setWriter(new \Slim\Logger\DateTimeFileWriter(['path' => './data', 'name_format' => 'Y-m-d']));
    $app->getLog()->info($appname . ' ' . $appversion . ': Running in debug mode.');
    error_reporting(E_ALL | E_STRICT);
    $app->getLog()->info('Running on PHP: ' . PHP_VERSION);
}

return function ($app, $settings) {
    $app->configureMode('production', 'confprod');
    $app->configureMode('development', 'confdev');
    $app->configureMode('debug', 'confdebug');
};
