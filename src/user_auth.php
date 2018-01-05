<?php

require_once('user_provider.php');

// oAuth2 server from AuthBucket (TODO):
// $app->register(new AuthBucket\OAuth2\Silex\Provider\AuthBucketOAuth2ServiceProvider());
// $app->register(new Silex\Provider\MonologServiceProvider());
// $app->register(new Silex\Provider\ValidatorServiceProvider());
//
//
// $fw = [
//   'api_oauth2_authorize' => [
//       'pattern' => '^/api/oauth2/authorize$',
//       'http' => true,
//       'users' => function () {
//           return new UserProvider();
//       },
//   ],
//   'api_oauth2_token' => [
//       'pattern' => '^/api/oauth2/token$',
//       'oauth2_token' => true,
//   ],
//   'api_oauth2_debug' => [
//       'pattern' => '^/api/oauth2/debug$',
//       'oauth2_resource' => true,
//   ],
//   'api_resource' => [
//       'pattern' => '^/api/resource',
//       'oauth2_resource' => true,
//   ]
// ];


// User accounts (WIP):

$fw['admin'] = [
    'pattern' => '^/adminZZZ',
    'form' => true,
    'http' => true,
    'users' => function () {
        return new UserProvider();
    },
];
$fw['unsecured'] = [
    'anonymous' => true, // make the user info accessible in non-secured areas
];

$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => $fw
));
