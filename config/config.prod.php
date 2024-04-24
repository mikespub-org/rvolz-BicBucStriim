<?php
/**
 * BicBucStriim config for production mode
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

use Psr\Log\LoggerInterface;

/**
 * Configure app for production
 */
return function ($app, $appname, $appversion) {
    $config = [
        'debug' => false,
        'cookies.lifetime' => '1 day',
        'cookies.secret_key' => 'b4924c3579e2850a6fad8597da7ad24bf43ab78e',

    ];
    foreach ($config as $name => $value) {
        $app->getContainer()->set($name, $value);
    }
    /** @var \BicBucStriim\Utilities\Logger $logger */
    $logger = $app->getContainer()->get(LoggerInterface::class);
    $logger->setEnabled(true);
    $logger->setMinLevel(\Psr\Log\LogLevel::WARNING, false);
    $logger->info($appname . ' ' . $appversion . ': Running in production mode.');
    $logger->info('Running on PHP: ' . PHP_VERSION);
    error_reporting(E_ALL ^ (E_DEPRECATED | E_USER_DEPRECATED));
};
