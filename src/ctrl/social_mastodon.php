<?php

class DataverseMastodon extends theCodingCompany\Mastodon {

	public $redirect_uri = '';
	public $app_config = [];

	function __construct() {
		global $bv;

		$this->redirect_uri = $bv->config->home_url.'/social/app/mastodon';

		$this->setMastodonDomain("social.coop"); // set mastodon instance domain

		if($bv->config->mastodon) $this->setCredentials($bv->config->mastodon); // use the keys from the file we stored in Step 1

 }

	 public function createApp(){
		 global $bv;

		 //Set our info
		 $this->app_config["scopes"] = "read write";
		 $this->app_config["client_name"] = $bv->config->app_name;
		 $this->app_config["website"]     = $bv->config->home_url;
		 $this->app_config["redirect_uris"] = $this->redirect_uri;

		 print_r($this->app_config);

		 return $this->getAppConfig();
	}

}


$app->get('/social/setup/mastodon', function () use ($app) {

	$t = new \DataverseMastodon();

	// Create a new App and get the client_id and client_secret
	$token_info = $t->createApp();

	print_r($token_info);

	return "<p>Save these 2 keys in your secrets.php config file.</p>";

});

$app->get('/social/auth/mastodon', function () use ($app) {
	global $bv;

	$t = new \DataverseMastodon();

	/**
	* Now that is set we can get the Authorization URL and redirect the user to Mastodon
	* After the user approves your App, it will return with an Access Token.
	*/
	$auth_url = $t->getAuthUrl();
	if($auth_url) header("Location: {$auth_url}&redirect_uri=".$t->redirect_uri, true);
	exit;

});


$app->get('/social/app/mastodon', function () use ($app) {
	global $bv;

	$t = new \DataverseMastodon();

	if($_GET['code']){

		$token_info = $t->getAccessToken($_GET['code']); //The access token you received by oauth redirect
		//The above '$token_info' will now give you a bearer token (If successfull), you also need to store that and keep it safe!

		print_r($t->getStatuses());
	}

	return '';

});
