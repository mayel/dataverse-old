<?php

use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;

use Silex\Provider\FormServiceProvider;

$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());

$app->register(new FormServiceProvider());
//$app->register(new AdamQuaile\Bundle\FieldsetBundle\AdamQuaileFieldsetBundle()); /// added

$app->register(new TwigServiceProvider());

use Symfony\Component\Form\FormRenderer;

$app->extend('twig.runtimes', function ($array) {
    $array[FormRenderer::class] = 'twig.form.renderer';
    return $array;
});

$app->register(new HttpFragmentServiceProvider());

$app->register(new Silex\Provider\SessionServiceProvider());


require_once('user_auth.php');


// Paginator
require_once('paginator_provider.php');
$app->register(new PaginatorServiceProvider());

// Localise
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.domains' => array(),
    'locale_fallbacks' => array('en'),
    'locale' => 'en'
));

$app['twig'] = $app->extend('twig', function ($twig, $app) {
    // add custom globals, filters, tags, ...

    return $twig;
});


return $app;
