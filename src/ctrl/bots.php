<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app->match('/bot', function (Request $request) use ($app) {
	global $bv;

	require_once($bv->base_path.'src/bots.inc.php');

	//bot_init(); 

	return new Response();
})
;

$app->get('/bot/setup/telegram', function () use ($app) { // load this page once to set up the webhook
	global $bv;

	require_once($bv->base_path.'src/bots.inc.php');

	$go = bot_setup_telegram();

	return $app->redirect($go);
})
;
