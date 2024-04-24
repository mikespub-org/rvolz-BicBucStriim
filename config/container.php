<?php
/**
 * BicBucStriim container
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

use Aura\Auth\Auth;
use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\Calibre\Calibre;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;

//$container = new Container();
$builder = new \DI\ContainerBuilder();
//$builder->enableDefinitionCache('BicBucStriim');
//$builder->enableCompilation(__DIR__ . '/cache');
//$builder->writeProxiesToFile(true, __DIR__ . '/cache');
//$builder->useAutowiring(false);
//$builder->useAttributes(false);
$builder->addDefinitions([
    // Application settings
    'settings' => fn() => require(__DIR__ . '/settings.php'),
    'mode' => !empty(getenv('BBS_DEBUG_MODE')) ? 'debug' : 'production',
    #'mode' => 'debug',
    #'mode' => 'development',
    LoggerInterface::class => function (ContainerInterface $c) {
        # Add null logger - see https://github.com/8ctopus/apix-log
        return new \BicBucStriim\Utilities\Logger();
    },
    CacheItemPoolInterface::class => function (ContainerInterface $c) {
        return new ApcuAdapter('BicBucStriim', 3600);
    },
    Auth::class => 'see login middleware',
    BicBucStriim::class => function (ContainerInterface $c) {
        # Freeze (true) DB schema before release! Set to false for DB development.
        return new BicBucStriim('data/data.db', true);
    },
    Calibre::class => function (ContainerInterface $c) {
        # Setup the connection to the Calibre metadata db
        $settings = $c->get('globalSettings');
        $clp = $settings->calibre_dir . '/metadata.db';
        return new Calibre($clp);
    },
    \Twig\Environment::class => function (ContainerInterface $c) {
        $loader = new \Twig\Loader\FilesystemLoader('templates');
        return new \Twig\Environment($loader);
    },
]);

$container = $builder->build();

return $container;
