<?php
/**
 * BicBucStriim container
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

use Psr\Container\ContainerInterface;

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
    //'view' => new \BicBucStriim\TwigView(),
    'mode' => 'production',
    #'mode' => 'debug',
    #'mode' => 'development',
    'twig' => function (ContainerInterface $c) {
        //return new Foo($c->get('db.host'));
        $loader = new \Twig\Loader\FilesystemLoader('templates');
        return new \Twig\Environment($loader);
    },
]);

$container = $builder->build();

return $container;
