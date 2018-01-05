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

		$msg .= sprintf('Using access_token %s --- ', $access_token);

	// With an access token, let's now get the user's details
		$account = $provider->getResourceOwner($access_token);

		// Use these details to match the user, or create a new profile
		$msg .= sprintf('Welcome %s! ', $account->getDisplayName());

		$msg .= sprintf('Your business name: %s ', $account->getBusinessName());
		$msg .= sprintf('Your currency: %s ', $account->getDefaultCurrency());
		// $msg .= sprintf('Your currencies: %s ', $account->getCurrenciesSupported());
		$msg .= sprintf('Your country: %s ', $account->getCountry());
		$msg .= sprintf('Your statement descriptor: %s ', $account->getStatementDescriptor());
		$msg .= sprintf('Your email: %s ', $account->getEmail());

		// Use this to interact with an API on the users behalf
		$user_token = $access_token->getToken();

		$msg .= sprintf('Ready to use user_token %s --- ', $user_token);

		return $msg;

	}
}


$app->get('/payments/setup/stripe', function () use ($app) {

	try {

	  $provider = new \DataverseStripe();

	  if (isset($_GET['access_token'])) { // Look up a user's data with a previously know access_token

			$msg = $provider->getUserData($_GET['access_token']);

		} elseif (!isset($_GET['code'])) { // If we don't have an access_token or authorization code then redirect the user to get one

	    $authUrl = $provider->getAuthorizationUrl();
	    $_SESSION['oauth2state'] = $provider->getState();
	    header('Location: '.$authUrl);
	    exit;

	  // Check given state against previously stored one to mitigate CSRF attack
	  } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

	      unset($_SESSION['oauth2state']);
	      return ('Invalid state. ');

	  } else {

			// Try to get an access token (using the authorization code grant)
	    $access_token = $provider->getAccessToken('authorization_code', [
	      'code' => $_GET['code']
	    ]);

			// Optional: Now you have a token you can look up a users profile data
			if($access_token) $msg = $provider->getUserData($access_token);

    }

	} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

    // Failed to get the access token or user details.
    $msg .= ('Oh dear... '.$e->getMessage());

  } catch (Exception $e) {

		$msg .= ('Oh dear... An unknown error occured. ');
	}

	return $msg;
})
;
