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

# Initial Application Name - display name can be changed in configuration
$appname = getenv('BBS_APP_NAME') ?: Settings::APP_NAME;
# App version
$appversion = Settings::APP_VERSION;
# Base path - null means undefined, empty '' or '/bbs' etc. mean predefined
$basepath = getenv('BBS_BASE_PATH') ?: null;
# Run mode
$mode = !empty(getenv('BBS_DEBUG_MODE')) ? 'debug' : 'production';
#$mode = 'debug';
#$mode = 'development';

# Init app globals
$settings = new Settings();
$settings['appname'] = $appname;
$settings['version'] = $appversion;
$settings['basepath'] = $basepath;
$settings['sep'] = ' :: ';
# Provide basic json api interface - configurable via environment variable
$settings['hasapi'] = getenv('BBS_HAS_API') ?: true;
$settings['origin'] = '*';

$knownConfigs = Settings::getKnownConfigs();
$settings->display_app_name = $appname;

return [
    'appname' => $appname,
    'appversion' => $appversion,
    'basepath' => $basepath,
    'globalSettings' => $settings,
    'knownConfigs' => $knownConfigs,
    'mode' => $mode,
];
