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

		if(!$bv->respondent) $ret->error = "No member found";
		else {

			$r = response_by_question_id(34, $bv->respondent->id); // get username

			$uname = $r->the_var ? $r->the_var : $r->answer->answer;

			$ret[0]->username = $uname;
			$ret[0]->email = $bv->respondent->email;
			$ret[0]->status = $bv->respondent->status;
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

		if(!$bv->respondent) $ret->error = "No member found";
		else {

			$ret = $bv->respondent;

		}

	} else {
		$ret->error = "Not allowed";
	}

  return new Response(json_encode($ret), 200, array(
      "Content-Type" => $request->getMimeType('json')
  ));

});

$app->post("/admin/api/member", function (Request $request) use ($app) {
	global $bv;

	if($_REQUEST['token']==$bv->config->admin_token){

		$status = $_REQUEST['status'];
		$email = $_REQUEST['email'];

		if($email) $bv->respondent = respondent_find($email);

		$ret = $bv->respondent;

		if(!$bv->respondent) $ret->error = "No member found";
		elseif($status) {

			$bv->respondent->status = $status;
			$ret->updated = R::store( $bv->respondent );

		}

	} else {
		$ret->error = "Not allowed";
	}

  return new Response(json_encode($ret), 200, array(
      "Content-Type" => $request->getMimeType('json')
  ));

});
