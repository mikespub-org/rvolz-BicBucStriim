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
require_once __DIR__ . '/constants.php';

use BicBucStriim\Utilities\L10n;
use BicBucStriim\Utilities\Mailer;

# Allowed languages, i.e. languages with translations
$allowedLangs = ['de', 'en', 'es', 'fr', 'gl', 'hu', 'it', 'nl', 'pl'];
# Fallback language if the browser prefers another than the allowed languages
$fallbackLang = 'en';
# Application Name
$appname = 'BicBucStriim';
# App version
$appversion = '1.7.2';
# Base path - null means undefined, empty '' or '/bbs' etc. mean predefined
$basepath = null;

# Init app globals
$globalSettings = [];
$globalSettings['appname'] = $appname;
$globalSettings['version'] = $appversion;
$globalSettings['basepath'] = $basepath;
$globalSettings['sep'] = ' :: ';
// @todo move this later in the request handling when we have $request available
# Find the user language, either one of the allowed languages or
# English as a fallback.
$globalSettings['lang'] = Utilities::getUserLang($allowedLangs, $fallbackLang);
$globalSettings['l10n'] = new L10n($globalSettings['lang']);
$globalSettings['langa'] = $globalSettings['l10n']->langa;
$globalSettings['langb'] = $globalSettings['l10n']->langb;
# Init admin settings with std values, for upgrades or db errors
$globalSettings[CALIBRE_DIR] = '';
$globalSettings[DB_VERSION] = DB_SCHEMA_VERSION;
$globalSettings[KINDLE] = 0;
$globalSettings[KINDLE_FROM_EMAIL] = '';
$globalSettings[THUMB_GEN_CLIPPED] = 1;
$globalSettings[PAGE_SIZE] = 30;
$globalSettings[DISPLAY_APP_NAME] = $appname;
$globalSettings[MAILER] = Mailer::MAIL;
$globalSettings[SMTP_USER] = '';
$globalSettings[SMTP_PASSWORD] = '';
$globalSettings[SMTP_SERVER] = '';
$globalSettings[SMTP_PORT] = 25;
$globalSettings[SMTP_ENCRYPTION] = 0;
$globalSettings[METADATA_UPDATE] = 0;
$globalSettings[LOGIN_REQUIRED] = 1;
$globalSettings[TITLE_TIME_SORT] = TITLE_TIME_SORT_TIMESTAMP;
$globalSettings[RELATIVE_URLS] = 1;

$knownConfigs = [CALIBRE_DIR, DB_VERSION, KINDLE, KINDLE_FROM_EMAIL,
    THUMB_GEN_CLIPPED, PAGE_SIZE, DISPLAY_APP_NAME, MAILER, SMTP_SERVER,
    SMTP_PORT, SMTP_USER, SMTP_PASSWORD, SMTP_ENCRYPTION, METADATA_UPDATE,
    LOGIN_REQUIRED, TITLE_TIME_SORT, RELATIVE_URLS];

return [
    'appname' => $appname,
    'appversion' => $appversion,
    'basepath' => $basepath,
    'globalSettings' => $globalSettings,
    'knownConfigs' => $knownConfigs,
];
