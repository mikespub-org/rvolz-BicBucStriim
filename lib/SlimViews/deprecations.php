<?php
/**
 * See https://twig.symfony.com/doc/1.x/deprecated.html
 * and https://twig.symfony.com/doc/1.x/recipes.html#deprecation-notices
 */
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$templateDir = dirname(__DIR__, 2) . '/templates';
$loader = new \Twig\Loader\FilesystemLoader($templateDir);
$twig = new \Twig\Environment($loader);

$deprecations = new \Twig\Util\DeprecationCollector($twig);
print_r($deprecations->collectDir($templateDir));
