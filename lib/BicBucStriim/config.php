<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2016 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

define('REDBEAN_MODEL_PREFIX', '\\BicBucStriim\\AppData\\Model_');

require_once __DIR__ . '/langs.php';
require_once __DIR__ . '/app_constants.php';
require_once __DIR__ . '/deprecated.php';

# The session gc lifetime needs to be at least as high as the Aura.Auth idle ttl, which defaults to 3600
ini_set('session.gc_maxlifetime', 3600);
# Running slim/slim 2.x on PHP 8.2 needs php error_reporting set to E_ALL & ~E_DEPRECATED & ~E_STRICT (= production default)
//error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

# Allowed languages, i.e. languages with translations
$allowedLangs = ['de', 'en', 'es', 'fr', 'gl', 'hu', 'it', 'nl', 'pl'];
# Fallback language if the browser prefers another than the allowed languages
$fallbackLang = 'en';
# Application Name
$appname = 'BicBucStriim';
# App version
$appversion = '1.6.6';

# Init app and routes
$app = new \Slim\Slim([
    'view' => new \Slim\Views\Twig(),
    'mode' => 'production',
    #'mode' => 'debug',
    #'mode' => 'development',
]);

$app->configureMode('production', 'confprod');
$app->configureMode('development', 'confdev');
$app->configureMode('debug', 'confdebug');

/**
 * Configure app for production
 */
function confprod()
{
    global $app, $appname, $appversion;
    $app->config([
        'debug' => false,
        'cookies.lifetime' => '1 day',
        'cookies.secret_key' => 'b4924c3579e2850a6fad8597da7ad24bf43ab78e',

    ]);
    $app->getLog()->setEnabled(true);
    $app->getLog()->setLevel(\Slim\Log::WARN);
    $app->getLog()->info($appname . ' ' . $appversion . ': Running in production mode.');
    $app->getLog()->info('Running on PHP: ' . PHP_VERSION);
    error_reporting(E_ALL ^ (E_DEPRECATED | E_USER_DEPRECATED));
}

/**
 * Configure app for development
 */
function confdev()
{
    global $app, $appname, $appversion;
    $app->config([
        'debug' => true,
        'cookies.lifetime' => '5 minutes',
        'cookies.secret_key' => 'b4924c3579e2850a6fad8597da7ad24bf43ab78e',

    ]);
    $app->getLog()->setEnabled(true);
    $app->getLog()->setLevel(\Slim\Log::DEBUG);
    $app->getLog()->info($appname . ' ' . $appversion . ': Running in development mode.');
    $app->getLog()->info('Running on PHP: ' . PHP_VERSION);
}

/**
 * Configure app for debug mode: production + log everything to file
 */
function confdebug()
{
    global $app, $appname, $appversion;
    $app->config([
        'debug' => true,
        'cookies.lifetime' => '1 day',
        'cookies.secret_key' => 'b4924c3579e2850a6fad8597da7ad24bf43ab78e',
    ]);
    $app->getLog()->setEnabled(true);
    $app->getLog()->setLevel(\Slim\Log::DEBUG);
    $app->getLog()->setWriter(new \Slim\Logger\DateTimeFileWriter(['path' => './data', 'name_format' => 'Y-m-d']));
    $app->getLog()->info($appname . ' ' . $appversion . ': Running in debug mode.');
    error_reporting(E_ALL | E_STRICT);
    $app->getLog()->info('Running on PHP: ' . PHP_VERSION);
}

# Init app globals
$globalSettings = [];
$globalSettings['appname'] = $appname;
$globalSettings['version'] = $appversion;
$globalSettings['sep'] = ' :: ';
# Find the user language, either one of the allowed languages or
# English as a fallback.
$globalSettings['lang'] = getUserLang($allowedLangs, $fallbackLang);
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

# Store $globalSettings in app config
$app->config('globalSettings', $globalSettings);

$knownConfigs = [CALIBRE_DIR, DB_VERSION, KINDLE, KINDLE_FROM_EMAIL,
    THUMB_GEN_CLIPPED, PAGE_SIZE, DISPLAY_APP_NAME, MAILER, SMTP_SERVER,
    SMTP_PORT, SMTP_USER, SMTP_PASSWORD, SMTP_ENCRYPTION, METADATA_UPDATE,
    LOGIN_REQUIRED, TITLE_TIME_SORT, RELATIVE_URLS];

# Freeze (true) DB schema before release! Set to false for DB development.
$app->bbs = new \BicBucStriim\AppData\BicBucStriim('data/data.db', true);
$app->add(new \BicBucStriim\Middleware\CalibreConfigMiddleware(CALIBRE_DIR));
$app->add(new \BicBucStriim\Middleware\LoginMiddleware($appname, ['js', 'img', 'style']));
$app->add(new \BicBucStriim\Middleware\OwnConfigMiddleware($knownConfigs));
$app->add(new \BicBucStriim\Middleware\CachingMiddleware(['/admin', '/login']));

return $app;
