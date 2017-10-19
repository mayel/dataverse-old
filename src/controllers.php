<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\HttpKernel\HttpKernelInterface;


//Request::setTrustedProxies(array('127.0.0.1'));


require_once('misc.php');
require_once('user_manage.php');
require_once('convo.php');

$app->get('/', function () use ($app) {
	return $app['twig']->render('index.html.twig', array());
})
->bind('homepage')
;


include_once('ctrl/question.php');

include_once('ctrl/embed.php');

include_once('ctrl/bots.php');

include_once('ctrl/build.php');

include_once('ctrl/browse.php');

include_once('ctrl/api.php');

include_once('ctrl/dev.php');


$app->error(function (\RedBeanPHP\RedException\SQL $e, Request $request, $code) {
    //echo "REDBEAN DEBUG E $e <p>R $request <p>code $code<p>";
    return;
});



// TODO
$app->error(function (\Exception $e, Request $request, $code) use ($app) {
	global $bv;

	if($bv->debug_mode){

//		if ($app['debug']) {
//			echo "GENERAL DEBUG E $e <p>R $request <p>code $code<p>";
//			return;
//		}

		// 404.html, or 40x.html, or 4xx.html, or error.html
		$templates = array(
			'errors/'.$code.'.html.twig',
			'errors/'.substr($code, 0, 2).'x.html.twig',
			'errors/'.substr($code, 0, 1).'xx.html.twig',
			'errors/default.html.twig',
		);

		return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);

	} else {
		$app->error(function (\Exception $e, Request $request, $code) {
		    return new Response('We are sorry, but something went wrong.');
		});
	}

});
