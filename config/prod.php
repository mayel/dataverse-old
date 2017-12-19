<?php
global $bv;

if (!$bv->base_path) {
    $bv->base_path = (dirname(dirname(__FILE__))).'/';
}

// configure your app for the production environment

$app['twig.path'] = array(__DIR__.'/../templates');
$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');

$app['debug'] = true; // temp!

require_once('secrets.php');
require_once('basics.php');
