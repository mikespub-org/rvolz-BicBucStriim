<?php

/**
 * BicBucStriim installation check
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

require_once 'vendor/autoload.php';

use BicBucStriim\AppData\Settings;
use BicBucStriim\Utilities\InstallUtil;

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, []);

# Check for Apache server
function is_apache($srv)
{
    if (preg_match('/apache/i', $srv)) {
        return true;
    } else {
        return false;
    }
}

# see http://christian.roy.name/blog/detecting-modrewrite-using-php
function mod_rewrite_enabled()
{
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        $mod_rewrite = in_array('mod_rewrite', $modules);
    } else {
        # Recent Apache versions (Synology DSM5) prefix envvars with the module name
        $mod_rewrite =  (getenv('HTTP_MOD_REWRITE') == 'On' || getenv('REDIRECT_HTTP_MOD_REWRITE') == 'On') ? true : false ;
    }
    return $mod_rewrite;
}

function has_sqlite()
{
    $version = false;
    try {
        $mydb = new PDO('sqlite:data/data.db', null, null, []);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function fw($file)
{
    return file_exists($file) && is_writeable($file);
}

function get_gd_version()
{
    ob_start();
    phpinfo(8);
    $module_info = ob_get_contents();
    ob_end_clean();
    return InstallUtil::find_gd_version($module_info);
}

function check_calibre($dir)
{
    clearstatcache();
    $ret = ['status' => 2, 'dir_exists' => false, 'dir_is_readable' => false, 'dir_is_executable' => false, 'realpath' => '', 'library_ok' => false];
    if (file_exists($dir)) {
        $ret['dir_exists'] = true;
        if (is_readable($dir)) {
            $ret['dir_is_readable'] = true;
            $ret['dir_is_executable'] = is_executable($dir);
            $mdb = realpath($dir) . '/metadata.db';
            $ret['realpath'] = $mdb;
            if (file_exists($mdb)) {
                $ret['status'] = 1;
                try {
                    $mydb = new PDO('sqlite:' . $mdb, null, null, []);
                    $ret['library_ok'] = true;
                } catch (PDOException $e) {
                    ;
                }
            }
        }
    }
    return $ret;
}

function check_php()
{
    $pv = preg_split('/\./', phpversion());
    $maj = intval($pv[0]);
    $min = intval($pv[1]);
    if ($maj == 8 && $min >= 0) {
        return true;
    } elseif ($maj > 8) {
        return true;
    } else {
        return false;
    }
}


if (isset($_POST['calibre_dir'])) {
    $calibre_dir = $_POST['calibre_dir'];
    $cd = check_calibre($calibre_dir);
} else {
    $calibre_dir = null;
    $cd = null;
}

$srv = $_SERVER['SERVER_SOFTWARE'];
$is_a = is_apache($srv) ;
if ($is_a) {
    $mre =  mod_rewrite_enabled();
} else {
    $mre = false;
}
$gdv = get_gd_version();
if ($gdv >= 2) {
    $gde = true;
} else {
    $gde = false;
}


$template = $twig->load('installcheck.twig');
echo $template->render([
    'page' => [
        'rot' => '',
        'version' => Settings::APP_VERSION,
    ],
    'is_a' => $is_a,
    'srv' => $srv,
    'mre' => $mre,
    'calibre_dir' => $calibre_dir,
    'cd' => $cd,
    'htaccess' => file_exists('./.htaccess'),
    'hsql' => has_sqlite(),
    'hgd2' => $gde,
    'hgd2v' => $gdv,
    'dwrit' => fw('./data'),
    'intl' => extension_loaded('intl'),
    'sodium' => extension_loaded('sodium'),
    'mwrit' => fw('./data/data.db'),
    'opd' => ini_get('open_basedir'),
    'php' => check_php(),
    'phpv' => phpversion(),
]);

#echo phpinfo();
