<?php

use Mailgun\Mailgun;

function get_include($file){
    ob_start();
    require($file);
    return ob_get_clean();
}

function email_send($msg="", $to="", $subject=false) {
	global $bv;

	# Instantiate the client.
	$mgClient = new Mailgun($bv->config->mail->mailgun_key);

	# Make the call to the client.
	return $mgClient->sendMessage($bv->config->mail->domain,
			  array('from'	=> $bv->config->mail->from,
					'to'	  => $to,
					'subject' => $subject ? $subject : $bv->config->mail->subject_default,
					'html'	=> $msg));

}


/**
 * Sanitizes a username, stripping out unsafe characters.
 *
 * Removes tags, octets, entities, and if strict is enabled, will only keep
 * alphanumeric, _, space, ., -, @. After sanitizing, it passes the username,
 * raw username (the username in the parameter), and the value of $strict as
 * parameters for the 'sanitize_user' filter.
 *
 * @since 2.0.0
 *
 * @param string $username The username to be sanitized.
 * @param bool $strict If set limits $username to specific characters. Default false.
 * @return string The sanitized username
 */
function sanitize_user( $username, $no_spaces = true, $lower=false ) {
	$username = strip_tags( $username );
	if($lower) $username = strtolower( $username );
	// Kill octets
	$username = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '', $username );
	$username = preg_replace( '/&.+?;/', '', $username ); // Kill entities

	$username = str_replace('-', '_', $username );

	// reduce to ASCII subset for max portability.
	$username = preg_replace( '|[^a-z0-9 _]|i', '', $username );

	// Consolidate contiguous whitespace
	$username = preg_replace( '|\s+|', ($no_spaces? '_': ' '), $username );
	if($no_spaces) $username = str_replace(' ', '_', $username );

	$username = trim( $username, ' _' );
	//echo $username;

	return $username;
}

function admin_auth(){
	global $bv, $app;

	$bv->user_token = $_GET['token'] ? $_GET['token'] : $app['session']->get('user_token'); // get from session

	if($bv->user_token !=$bv->config->admin_token) exit("Unauthorized!"); // no good

	$app['session']->set('user_token', $bv->user_token); // OK, save as session
}
