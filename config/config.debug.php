<?php
/**
 * BicBucStriim config for debug mode
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

use Psr\Log\LoggerInterface;

/**
 * Configure app for debug mode: production + log everything to file
 */
return function ($app, $appname, $appversion) {
    $config = [
        'debug' => true,
        'cookies.lifetime' => '1 day',
        'cookies.secret_key' => 'b4924c3579e2850a6fad8597da7ad24bf43ab78e',
    ];
    foreach ($config as $name => $value) {
        $app->getContainer()->set($name, $value);
    }
    /** @var \BicBucStriim\Utilities\Logger $logger */
    $logger = $app->getContainer()->get(LoggerInterface::class);
    $logger->setEnabled(true);
    $logger->setMinLevel(\Psr\Log\LogLevel::DEBUG);
    // replacement for DateTimeFileWriter that supports Psr\Log
    $logger->add(new \Apix\Log\Logger\File('./data/debug-' . date('Y-m-d') . '.log'));
    $logger->info($appname . ' ' . $appversion . ': Running in debug mode.');
    error_reporting(E_ALL | E_STRICT);
    $logger->info('Running on PHP: ' . PHP_VERSION);
};
