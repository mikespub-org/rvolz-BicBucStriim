<?php
/**
 * BicBucStriim routes
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim;

function getActions($settings)
{
    return [
        // classname, prefix
        [Actions\MainActions::class, null],
        [Actions\AdminActions::class, '/admin'],
        [Actions\MetadataActions::class, '/metadata'],
        [Actions\OpdsActions::class, '/opds'],
    ];
}

###### Init routes for production
return function ($app, $settings) {
    $actions = getActions($settings);
    foreach ($actions as [$class, $prefix]) {
        $class::addRoutes($app, $prefix);
    }
    if (!empty($settings['globalSettings']['hasapi'])) {
        $class = Actions\ApiActions::class;
        $class::addRoutes($app, '/api');
    }
};
