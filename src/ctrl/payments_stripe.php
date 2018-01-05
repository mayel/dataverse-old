<?php
use Symfony\Component\HttpFoundation\Request;

class DataverseStripe extends AdamPaterson\OAuth2\Client\Provider\Stripe {

	function __construct(array $options = []) {
		global $bv;

		parent::__construct([
	     'clientId'          => $bv->config->stripe->connect->client,
	     'clientSecret'      => $bv->config->stripe->secret,
	     'redirectUri'       => $bv->config->home_url.'/payments/setup/stripe',
	 	]);
 }

	public function getDefaultScopes()
	{
			return ['read_write'];
	}

	public function getUserData($access_token){

		global $msg;

		print_r($access_token);

		$msg .= '<br>Access Token: ' . $access_token->getToken() . "<br>";
    $msg .= 'Refresh Token: ' . $access_token->getRefreshToken() . "<br>";

    // $msg .= 'Expired in: ' . $access_token->getExpires() . "<br>"; // apparently no expiry
    // $msg .= 'Already expired? ' . ($access_token->hasExpired() ? 'expired' : 'not expired') . "<br>";

	// With an access token, let's now get the user's details
		$account = $this->getResourceOwner($access_token);

		if($account){
			// Use these details to match the user, or create a new profile
			$msg .= sprintf('<br>Welcome %s! ', $account->getDisplayName());

			$msg .= sprintf('<br>Your business name: %s ', $account->getBusinessName());
			$msg .= sprintf('<br>Your currency: %s ', $account->getDefaultCurrency());
			// $msg .= sprintf('Your currencies: %s ', $account->getCurrenciesSupported());
			$msg .= sprintf('<br>Your country: %s ', $account->getCountry());
			$msg .= sprintf('<br>Your statement descriptor: %s ', $account->getStatementDescriptor());
			$msg .= sprintf('<br>Your email: %s ', $account->getEmail());
		}

	}
}


$app->get('/payments/setup/stripe', function () use ($app) {
	global $msg;

	try {

	  $provider = new \DataverseStripe();

	  if (isset($_GET['stoken'])) { // can look up a user's data with a previously know access_token

			$access_token = new League\OAuth2\Client\Token\AccessToken([
				'access_token' => $_GET['stoken'],
				'refresh_token' => $_GET['rtoken']
			]); // recreate token

		} elseif (!isset($_GET['code'])) { // If we don't have an access_token or authorization code then redirect the user to get one

	    $authUrl = $provider->getAuthorizationUrl();
	    $_SESSION['oauth2state'] = $provider->getState();
	    if($authUrl) header('Location: '.$authUrl);
	    exit;

		} elseif (!empty($_GET['state']) && ($_GET['state'] == $_SESSION['oauth2state'])) { // Check given state against previously stored one to mitigate CSRF attack

			// Try to get an access token (using the authorization code grant)
	    $access_token = $provider->getAccessToken('authorization_code', [
	      'code' => $_GET['code']
	    ]);

		} else {

			$msg .= ('Invalid state. ');
			unset($_SESSION['oauth2state']);

    }


		// Optional: if we have a token, look up a user's profile data
		if($access_token) $msg .= $provider->getUserData($access_token);


	} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

    // Failed to get the access token or user details.
    $msg .= ('Oh dear... '.$e->getMessage());

  } catch (Exception $e) {

		$msg .= ('Oh dear... An unknown error occured. '.$e->getMessage());
	}

	return $msg;
})
;
