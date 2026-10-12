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

use BicBucStriim\AppData\Settings;
use BicBucStriim\Framework\ActionLoader;
use BicBucStriim\Utilities\RouteUtil;

###### Init routes for production
return function ($app, $settings, $gatekeeper) {
    /** @var \Psr\Container\ContainerInterface $container */
    $container = $app->getContainer();
    /** @var ActionRegistry $registry */
    $registry = $container->get(ActionRegistry::class);
    /** @var ?Settings $globalSettings */
    $globalSettings = $settings['globalSettings'] ?? null;

    // Single source of truth for which action classes to load and their URL
    // prefixes, honoring the 'hasapi' setting.
    foreach (ActionLoader::getActionDescriptors($globalSettings) as [$class, $prefix]) {
        $registry->register($class, $prefix);
        RouteUtil::addActionRoutes($app, $class, $prefix, $gatekeeper);
    }
};
