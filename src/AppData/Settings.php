<?php
/**
 * BicBucStriim constants
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\AppData;

use BicBucStriim\Utilities\Mailer;
use ArrayObject;

/**
 * @property string $calibre_dir
 * @property int $db_version
 * @property int $thumb_gen_clipped
 * @property int $kindle
 * @property string $kindle_from_email
 * @property int $page_size
 * @property string $display_app_name
 * @property string $mailer
 * @property int $metadata_update
 * @property int $must_login
 * @property string $title_time_sort
 * @property int $relative_urls
 * @property string $templates_dir
 */
class Settings extends ArrayObject
{
    # Application Name
    public const APP_NAME = 'BicBucStriim';

    # Application version
    public const APP_VERSION = '3.5.0';

    # Current DB schema version
    public const DB_SCHEMA_VERSION = 3;

    # URL for version information
    public const VERSION_URL = 'https://github-org.mikespub.net/rvolz-BicBucStriim/version.json';

    # Cookie name to store Kindle email address
    public const KINDLE_COOKIE = 'kindle_email';
    # Calibre library path
    public const CALIBRE_DIR = 'calibre_dir';
    # BicBucStriim DB version
    public const DB_VERSION = 'db_version';
    # Thumbnail generation method
    public const THUMB_GEN_CLIPPED = 'thumb_gen_clipped';
    # Send-To-Kindle enabled/disabled
    public const KINDLE = 'kindle';
    # Send-To-Kindle from-address
    public const KINDLE_FROM_EMAIL = 'kindle_from_email';
    # Page size for list views, no. of elemens
    public const PAGE_SIZE = 'page_size';
    # Displayed app name for page title
    public const DISPLAY_APP_NAME = 'display_app_name';
    # Kind of mail support used
    public const MAILER = 'mailer';
    # Name of SMTP server, if SMTP mailer is used
    public const SMTP_SERVER = 'smtp_server';
    # Port of SMTP server, if SMTP mailer is used
    public const SMTP_PORT = 'smtp_port';
    # SMTP user name, if SMTP mailer is used
    public const SMTP_USER = 'smtp_user';
    # SMTP password, if SMTP mailer is used
    public const SMTP_PASSWORD = 'smtp_password';
    # SMTP encryption, if SMTP mailer is used
    public const SMTP_ENCRYPTION = 'smtp_encryption';
    # if true then the metadata of books is updated before download
    public const METADATA_UPDATE = 'metadata_update';
    # if true then login is required
    public const LOGIN_REQUIRED = 'must_login';
    # field for time-sorting of books
    public const TITLE_TIME_SORT = 'title_time_sort';
    # Possible values for the above field
    public const TITLE_TIME_SORT_TIMESTAMP = 'timestamp';
    public const TITLE_TIME_SORT_PUBDATE = 'pubdate';
    public const TITLE_TIME_SORT_LASTMODIFIED = 'lastmodified';
    # if true then relative urls will be generated
    public const RELATIVE_URLS = 'relative_urls';
    # custom templates directory that overrides the default
    public const TEMPLATES_DIR = 'templates_dir';

    public static function getKnownConfigs()
    {
        return array_keys(self::initSettings());
    }

    /**
     * Init admin settings with std values, for upgrades or db errors
     */
    public static function initSettings()
    {
        return [
            self::CALIBRE_DIR => '',
            self::DB_VERSION => self::DB_SCHEMA_VERSION,
            self::KINDLE => 0,
            self::KINDLE_FROM_EMAIL => '',
            self::THUMB_GEN_CLIPPED => 1,
            self::PAGE_SIZE => 30,
            self::DISPLAY_APP_NAME => self::APP_NAME,
            self::MAILER => Mailer::MAIL,
            self::SMTP_USER => '',
            self::SMTP_PASSWORD => '',
            self::SMTP_SERVER => '',
            self::SMTP_PORT => 25,
            self::SMTP_ENCRYPTION => 0,
            self::METADATA_UPDATE => 0,
            self::LOGIN_REQUIRED => 1,
            self::TITLE_TIME_SORT => self::TITLE_TIME_SORT_TIMESTAMP,
            self::RELATIVE_URLS => 1,
            self::TEMPLATES_DIR => '',
        ];
    }

    public function __construct($settings = [])
    {
        $settings = array_replace($this->initSettings(), $settings);
        parent::__construct($settings, ArrayObject::ARRAY_AS_PROPS);
    }
}
