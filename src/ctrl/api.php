<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
// use Silex\Provider\SerializerServiceProvider;

// $app->register(new SerializerServiceProvider());

$app->get("/admin/api/members", function (Request $request) use ($app) {
	global $bv;

	if($_REQUEST['token']==$bv->config->admin_token){

		$status = $_REQUEST['status'] ? $_REQUEST['status'] : 'invite';

		$bv->respondent = a_respondent_by_status($status);

		if(!$bv->respondent) $ret->error = "No member found with status: ".$status;
		else {

			$ret[0] = member_get($bv->respondent);
		}

	} else {
		$ret->error = "Not allowed";
	}

  return new Response(json_encode($ret), 200, array(
      "Content-Type" => $request->getMimeType('json')
  ));

});

$app->get("/admin/api/member", function (Request $request) use ($app) {
	global $bv;

	if($_REQUEST['token']==$bv->config->admin_token){

		$email = $_REQUEST['email'];

		if($email) $bv->respondent = respondent_find($email);

		if(!$bv->respondent) $ret->error = "No such member found";
		else {

			$ret = member_get($bv->respondent);

		}

	} else {
		$ret->error = "Not allowed";
	}

  return new Response(json_encode($ret), 200, array(
      "Content-Type" => $request->getMimeType('json')
  ));

});

$app->post("/admin/api/member", function (Request $request) use ($app) {

	$ret = member_update();

  return new Response(json_encode($ret), 200, array(
      "Content-Type" => $request->getMimeType('json')
  ));

});

$app->get("/admin/api/member/update", function (Request $request) use ($app) {

	$ret = member_update();

  return new Response(json_encode($ret), 200, array(
      "Content-Type" => $request->getMimeType('json')
  ));

});

function member_get($u){
	global $bv;

	include_once($bv->base_path."custom/config_invite.php");

	if(function_exists('username_by_respondent_id')) $u->username = username_by_respondent_id($u->id);
	if(!$u->username) $u->error .= "Could not find the username. ";

	// $u->username = $username;
	// $u->email = $bv->respondent->email;
	// $u->status = $bv->respondent->status;

	if($u->status=="invite"){
		$pw = bin2hex(openssl_random_pseudo_bytes(4));
		$u->password_random = $pw;
	}

	return $u;
}

function member_update(){
	global $bv;

	if($_REQUEST['token']==$bv->config->admin_token){

		$email = $_REQUEST['email'];
		$status = $_REQUEST['status'];

		if($email) $bv->respondent = respondent_find($email);

		$ret = $bv->respondent;

		if(!$bv->respondent) $ret->error = "No such member found. ";

		elseif($status) { // update status

			if($status=='created'){

				$ret->account_email_sent = false;

				$pw = $_REQUEST['password'];

				include_once($bv->base_path."custom/config_invite.php");

				if(function_exists('username_by_respondent_id')) $username = username_by_respondent_id($bv->respondent->id);
				if(!$username) $ret->error .= "Could not find the username. ";

				if($pw && $username){

					if(function_exists('send_mastodon_account_email') && send_mastodon_account_email($email, $username, $pw)) $ret->account_email_sent = true;
					else $ret->error .= "Error trying to send confirmation email. ";

				} else $ret->error .= "The confirmation email could not be sent. (Make sure you include the new account's password in the request). ";

			}

			$bv->respondent->status = $status;
			$ret->updated = R::store( $bv->respondent );

		}

	} else {
		$ret->error = "Not allowed";
	}

	return $ret;
}
