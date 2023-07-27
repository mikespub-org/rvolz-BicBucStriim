<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {
    // Global Settings Object
    $containerBuilder->addDefinitions([
        'settings' => [
            // mode
            'debug' => true,

            // @todo replace BBS_BASE_PATH from public/bbs-config.php + make configurable via env
            // If not installed at root, enter the path to the installation here
            'basePath' => '',

            // Display call stack in orignal slim error when debug is off
            'displayErrorDetails' => true, // TODO set to false in production
            'addContentLengthHeader' => false, // Allow the web server to send the content-length header
            // Renderer settings
            'renderer' => [
                'template_path' => __DIR__ . '/../app/templates/',
                'cache_path' => __DIR__ . '/../var/cache',
            ],
            // Monolog settings
            'logger' => [
                'name' => 'BicBucStriim',
                'path' => getenv('docker') ? 'php://stdout' : __DIR__ . '/../var/logs/app.log',
                'level' => Logger::DEBUG,
            ],
            // BicBucStriim settings
            'bbs' => [
                'dataDb' => __DIR__ . '/../data/data.db',
                'public' => __DIR__ . '/../public',
                // TODO get version from outside
                'version' => '2.0.0-alpha.1',
                'langs' => ['de', 'en', 'es', 'fr', 'gl', 'hu', 'it', 'nl', 'pl'],
            ],
        ],
    ]);
};
