<?php
/**
 * BicBucStriim settings
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

use BicBucStriim\AppData\Settings;

# Application Name
$appname = getenv('BBS_APP_NAME') ?: 'BicBucStriim';
# App version
$appversion = '3.3.0';
# Base path - null means undefined, empty '' or '/bbs' etc. mean predefined
$basepath = getenv('BBS_BASE_PATH') ?: null;

# Init app globals
$settings = new Settings();
$settings['appname'] = $appname;
$settings['version'] = $appversion;
$settings['basepath'] = $basepath;
$settings['sep'] = ' :: ';
// provide basic json api interface - make configurable via environment variable
$settings['hasapi'] = getenv('BBS_HAS_API') ?: false;

$knownConfigs = Settings::getKnownConfigs();
$settings->display_app_name = $appname;

return [
    'appname' => $appname,
    'appversion' => $appversion,
    'basepath' => $basepath,
    'globalSettings' => $settings,
    'knownConfigs' => $knownConfigs,
];
