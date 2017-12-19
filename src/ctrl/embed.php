<?php
use Symfony\Component\HttpFoundation\Request;

$app->match('/embed', function (Request $request) use ($app) {
    global $bv;

    $bv->page_title = "Embed";

    return $app['twig']->render('embed-demo.html.twig', array('data' => $data, 'id' => $_GET['id'], 'home_url'=>$bv->config->home_url ));
});
