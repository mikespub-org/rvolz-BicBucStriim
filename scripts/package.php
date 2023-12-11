<?php

use Alchemy\Zippy\Zippy;

// Require Composer's autoloader
require __DIR__ . '/../vendor/autoload.php';

// Load Zippy
$zippy = Zippy::load();
$archive = $zippy->create('bicbucstriim.zip', [
    'img' => 'img',
    'js' => 'js',
    'style/style.css' => 'style/style.css',
    'style/jquery' => 'style/jquery',
    'config' => 'config',
    'src' => 'src',
    'vendor' => 'vendor',
    'templates' => 'templates',
    'data' => 'data',
    'index.php',
    'installcheck.php',
    'php.ini',
    'favicon.ico',
    'bbs-icon.png',
    'CHANGELOG.md',
    '.htaccess' => '.htaccess',
    'NOTICE',
    'LICENSE',
    'README.md',
], true);
