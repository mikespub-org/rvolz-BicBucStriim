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

function getActions($settings)
{
    return [
        // classname, prefix
        [MainActions::class, null],
        [AdminActions::class, '/admin'],
        [MetadataActions::class, '/metadata'],
        [OpdsActions::class, '/opds'],
    ];
}

###### Init routes for production
return function ($app, $settings, $gatekeeper) {
    $actions = getActions($settings);
    foreach ($actions as [$class, $prefix]) {
        $class::addRoutes($app, $prefix, $gatekeeper);
    }
    if (!empty($settings['globalSettings']['hasapi'])) {
        $class = ApiActions::class;
        $class::addRoutes($app, '/api', $gatekeeper);
    }
};
