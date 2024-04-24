<?php
/**
 * BicBucStriim settings
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

require_once __DIR__ . '/langs.php';

use BicBucStriim\AppData\Settings;
use BicBucStriim\Utilities\InputUtil;
use BicBucStriim\Utilities\L10n;

# Allowed languages, i.e. languages with translations
$allowedLangs = ['de', 'en', 'es', 'fr', 'gl', 'hu', 'it', 'nl', 'pl'];
# Fallback language if the browser prefers another than the allowed languages
$fallbackLang = 'en';
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
// @todo move this later in the request handling when we have $request available
# Find the user language, either one of the allowed languages or
# English as a fallback.
$settings['lang'] = InputUtil::getUserLang($allowedLangs, $fallbackLang);
$settings['l10n'] = new L10n($settings['lang']);
$settings['langa'] = $settings['l10n']->langa;
$settings['langb'] = $settings['l10n']->langb;

$knownConfigs = Settings::getKnownConfigs();
$settings->display_app_name = $appname;

return [
    'appname' => $appname,
    'appversion' => $appversion,
    'basepath' => $basepath,
    'globalSettings' => $settings,
    'knownConfigs' => $knownConfigs,
];
