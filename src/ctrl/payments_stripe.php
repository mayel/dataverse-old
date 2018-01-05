<?php
use Symfony\Component\HttpFoundation\Request;

$app->get('/payments/setup/stripe', function () use ($app) {
  global $bv;

  $provider = new \AdamPaterson\OAuth2\Client\Provider\Stripe([
    'clientId'          => $bv->config->stripe->connect->client,
    'clientSecret'      => $bv->config->stripe->connect->secret,
    'redirectUri'       => $bv->config->home_url.'/payments/setup/stripe',
	]);

  if (!isset($_GET['code'])) { // If we don't have an authorization code then get one

    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

  // Check given state against previously stored one to mitigate CSRF attack
  } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

      unset($_SESSION['oauth2state']);
      return ('Invalid state');

  } else {

    try {

			// Try to get an access token (using the authorization code grant)
	    $access_token = $provider->getAccessToken('authorization_code', [
	      'code' => $_GET['code']
	    ]);


			// Optional: Now you have a token you can look up a users profile data

      // We got an access token, let's now get the user's details
      $account = $provider->getResourceOwner($access_token);

      // Use these details to create a new profile
      $msg .= sprintf('Welcome %s! ', $account->getDisplayName());


			// Use this to interact with an API on the users behalf
			$user_token = $access_token->getToken();

			$msg .= sprintf('Ready to use token %s! ', $user_token);

    } catch (Exception $e) {

      // Failed to get user details
			print_r($e);
      $msg .= ('Oh dear... An error occured. ');
    }

		return $msg;
  }
})
;
