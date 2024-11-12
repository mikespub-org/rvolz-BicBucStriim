<?php
/**
 * EPub Loader config for ExtraActions::loader()
 * @see https://github.com/mikespub-org/epub-loader
 */

$gConfig = [];

/**
 * URL endpoint for your application
 */
$gConfig['endpoint'] = $_SERVER['SCRIPT_NAME'] ?? null;

/**
 * Application name
 */
$gConfig['app_name'] = 'EPub Loader';

/**
 * Admin email
 */
$gConfig['admin_email'] = '';

/**
 * Create Calibre databases ?
 *
 * If true: databases are removed and recreated before loading ebooks
 * If false: append ebooks into databases
 */
$gConfig['create_db'] = false;

/**
 * Specify a cache directory for any Google or Wikidata lookup
 */
$gConfig['cache_dir'] = dirname(__DIR__) . '/tests/cache';

/**
 * Specify a template directory to override the standard templates
 */
//$gConfig['template_dir'] = dirname(__DIR__) . '/templates/loader';

/**
 * Databases infos - will be re-loaded from $config['calibre_directory']
 *
 * For each database:
 *   name: The database name to display
 *   db_path: The path where to create the database
 *   epub_path: The relative path from db_path where to look for the epub files
 */
$gConfig['databases'] = [];
$gConfig['databases'][] = ['name' => 'Test Library 2', 'db_path' => dirname(__DIR__) . '/tests/fixtures/lib2', 'epub_path' => '.'];
$gConfig['databases'][] = ['name' => 'Test Library 4', 'db_path' => dirname(__DIR__) . '/tests/fixtures/lib4', 'epub_path' => '.'];

/**
 * Available action groups
 */
$gConfig['groups'] = [];
// only if you start without an existing calibre database
$gConfig['groups']['Import'] = [
    //'db_load' => 'Create Calibre database with available epub files',
    //'csv_import' => 'Import CSV records into new Calibre database',
    //'json_import' => 'Import JSON records into new Calibre database',
    //'cache_load' => 'Load JSON files from Lookup cache into new Calibre database',
];
$gConfig['groups']['Export'] = [
    'csv_export' => 'Export CSV records with available epub files',
    'csv_dump' => 'Dump CSV records from Calibre database',
    'json_export' => 'Export JSON records with available epub files',
    'json_dump' => 'Dump JSON records from Calibre database',
    // if configured by calling application
    'callback' => 'Export metadata cache info via callbacks',
];
$gConfig['groups']['Lookup'] = [
    'authors' => 'Authors in database',
    'wd_author' => 'WikiData authors',
    'wd_books' => 'WikiData books for author',
    'wd_series' => 'WikiData series for author',
    'gb_books' => 'Google Books for author',
    'ol_author' => 'OpenLibrary authors',
    'ol_books' => 'OpenLibrary books for author',
    'gr_author' => 'GoodReads authors',
    'gr_books' => 'GoodReads books for author',
    'gr_series' => 'GoodReads series',
    'caches' => 'Cache statistics',
];
// internal actions are not shown on the main menu
$gConfig['groups']['Internal'] = [
    'books' => 'Books in database',
    'series' => 'Series in database',
    'test' => 'Test action (not visible)',
    'wd_entity' => 'WikiData entity',
    'gb_volume' => 'Google Books volume',
    'ol_work' => 'OpenLibrary work',
    'resource' => 'Get Calibre Resource',
];
$gConfig['groups']['Extra'] = [
    'notes' => 'Get Calibre Notes',
    // update metadata in epub files
    //'meta' => 'EPub Metadata App',
    'hello_world' => 'Example: Hello, World - see app/example.php',
    // disable any other actions you don't want to use via COPS
    //'goodbye' => 'Example: Goodbye - see app/example.php',
];

return $gConfig;
