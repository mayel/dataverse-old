<?php
use Symfony\Component\Security\Core\User\User;

function user_init() {
	global $app, $bv;
	$token = $app['security.token_storage']->getToken();

	if (null !== $token) {
	    $bv->user = $token->getUser();
	} 

	if($bv->user=='anon.') $bv->user = null;
}

function user_create($username, $password, $email, $roles=[]) {
	global $app, $bv;

	if(!$username) $username = $email; // fallback on email

	$bv->user = new User($username); // init class

	$u = R::dispense( 'user' ); // init DB table

	$u->setMeta("buildcommand.unique.0" , array('username'));

	$u->username = $username;
	$u->email = $email;

	$u->roles = implode(',', $roles);

	$encoder = $app['security.encoder_factory']->getEncoder($bv->user);

	// compute the encoded password for foo
	$password = $encoder->encodePassword($password, $bv->user->getSalt());

	$u->password = $password;


	try { // save

		$user_id = R::store( $u );

	} catch(Exception $e) {

	    error_log("Could not create user (probably duplicate)");
	    // TODO
	}

	return $user_id;
}
