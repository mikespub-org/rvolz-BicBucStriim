<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2016 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

require 'vendor/autoload.php';

# Init app and routes
$app = require(__DIR__ . '/config/config.php');

$app->run();
