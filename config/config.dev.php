<?php
/**
 * BicBucStriim config for development mode
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

use Psr\Log\LoggerInterface;

/**
 * Configure app for development
 */
return function ($app, $appname, $appversion) {
    $config = [
        'debug' => true,
        'cookies.lifetime' => '5 minutes',
        'cookies.secret_key' => 'b4924c3579e2850a6fad8597da7ad24bf43ab78e',

    ];
    foreach ($config as $name => $value) {
        $app->getContainer()->set($name, $value);
    }
    /** @var \BicBucStriim\Utilities\Logger $logger */
    $logger = $app->getContainer()->get(LoggerInterface::class);
    $logger->setEnabled(true);
    $logger->setMinLevel(\Psr\Log\LogLevel::DEBUG, false);
    $logger->info($appname . ' ' . $appversion . ': Running in development mode.');
    $logger->info('Running on PHP: ' . PHP_VERSION);
};
