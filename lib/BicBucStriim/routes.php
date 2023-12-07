<?php
/**
 * BicBucStriim routes
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

require_once __DIR__ . '/Actions/main.php';
require_once __DIR__ . '/Actions/admin.php';
//require_once __DIR__ . '/Actions/authors.php';
require_once __DIR__ . '/Actions/metadata.php';
require_once __DIR__ . '/Actions/opds.php';
//require_once __DIR__ . '/Actions/series.php';
//require_once __DIR__ . '/Actions/tags.php';
//require_once __DIR__ . '/Actions/titles.php';

use BicBucStriim\Actions\AdminActions;
use BicBucStriim\Actions\MainActions;
use BicBucStriim\Actions\MetadataActions;
use BicBucStriim\Actions\OpdsActions;

###### Init routes for production
return function ($app) {
    MainActions::addRoutes($app);
    AdminActions::addRoutes($app, '/admin');
    MetadataActions::addRoutes($app, '/metadata');
    OpdsActions::addRoutes($app, '/opds');
};
