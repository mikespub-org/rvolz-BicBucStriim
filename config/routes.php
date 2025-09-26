<?php

/**
 * BicBucStriim routes
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Actions;

if (!function_exists('\BicBucStriim\Actions\getActions')) {
    function getActions($settings)
    {
        return [
            // classname, prefix
            [MainActions::class, null],
            [AdminActions::class, '/admin'],
            [MetadataActions::class, '/metadata'],
            [OpdsActions::class, '/opds'],
            [ExtraActions::class, '/extra'],
        ];
    }
}

###### Init routes for production
return function ($app, $settings, $gatekeeper) {
    /** @var \Psr\Container\ContainerInterface $container */
    $container = $app->getContainer();
    /** @var ActionRegistry $registry */
    $registry = $container->get(ActionRegistry::class);

    // Get the list of actions from the helper function
    $actions = getActions($settings);

    // Loop through the actions to register them and add their routes
    foreach ($actions as [$class, $prefix]) {
        $registry->register($class);
        $class::addRoutes($app, $prefix, $gatekeeper);
    }

    // Handle the special case for the API
    if (!empty($settings['globalSettings']['hasapi'])) {
        $class = ApiActions::class;
        $registry->register($class);
        $class::addRoutes($app, '/api', $gatekeeper);
    } else {
        // fall back on hello world
        $class = DefaultActions::class;
        $registry->register($class);
        $class::addRoutes($app, '/api');
    }
};
