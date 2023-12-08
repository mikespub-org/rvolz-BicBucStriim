<?php
/**
 * BicBucStriim routes
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

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
