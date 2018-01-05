<?php

// oAuth2 server from AuthBucket (TODO):

$app->get('/api/oauth2/authorize', 'authbucket_oauth2.authorization_controller:indexAction')
    ->bind('api_oauth2_authorize');

$app->post('/api/oauth2/token', 'authbucket_oauth2.token_controller:indexAction')
    ->bind('api_oauth2_token');

$app->match('/api/oauth2/debug', 'authbucket_oauth2.debug_controller:indexAction')
    ->bind('api_oauth2_debug');
