<?php
use Symfony\Component\HttpFoundation\Request;

use Kosinix\Pagination;

$app->get('/admin/members/{page}/{sort_by}/{sorting}', function (Request $request, $page, $sort_by, $sorting) use ($app) {
	global $bv;

	admin_auth();

	$bv->questionnaire_id = $_GET['questionnaire'] ? $_GET['questionnaire'] : $app['session']->get('questionnaire'); // get from session

	if(!$bv->questionnaire_id) $bv->questionnaire_id = 2;

  $count = (int) R::count( 'respondent', ' questionnaire_id = ? AND email IS NOT NULL ', [ $bv->questionnaire_id ] );

	if($count>0){

	$app['session']->set('questionnaire', $bv->questionnaire_id); // save as session

  /** @var \Kosinix\Paginator $paginator */
  $paginator =  $app['paginator']($count, $page);

	$limits = ($bv->db_type == 'postgres' ? ' LIMIT ? OFFSET ? ' : ' LIMIT ? , ? ');

	if($bv->db_type == 'postgres') $params = [ $bv->questionnaire_id, $paginator->getPerPage(), $paginator->getStartIndex() ];
	else $params = [ $bv->questionnaire_id, $paginator->getStartIndex(), $paginator->getPerPage() ];

	// var_dump($count, $page, $sort_by, ($sorting), $limits, $params);

  if($count) $people = R::find( 'respondent', " questionnaire_id = ? AND email IS NOT NULL
  ORDER BY $sort_by $sorting ".$limits,  $params);

	foreach ($people as $p) {
		$responses = R::find( 'response', ' respondent_id = ?
		ORDER BY response_ts ASC', [ $p->id ]);
		//print_r($responses);

		foreach ($responses as $r) {

			echo '<p>';
			$c = $r->the_var ? $r->the_var : $r->answer->answer;

			$f = $r->question->question_name; // missing

//			if(!$f){ // fix missing Q link - no longer needed
//
//				if($r->answer){
//					$match_questions = $r->answer->sharedQuestionList;
//					foreach ($match_questions as $q) {
//						if($q->questionnaire_id == $questionnaire_id){ $f = $q->question_name; $q_ok = $q; }
//					}
//				}
//
//				if(!$f){
//					if(strpos($c, 'http')===0){ $f = 'site'; $qid=37; }
//					elseif(strpos($c, '@')){ $f = 'email'; $qid=32; }
//					elseif(strlen($c)==2){ $f = 'country'; $qid=39; }
//					elseif($r->the_var){ $f = 'comment'; $qid=38; }
//					elseif(!$p->name){ $f = 'name'; $qid=33; }
//					else{ $f = 'username'; $qid=34; }
//				}
//
//				if($f){
//					if(!$q_ok && $qid) $q_ok = question_get($qid);
//
//					if($q_ok){
//						$r->question = $q_ok;
//						R::store( $r );
//					}
//				}
//			}

			if($f){

				if($f !='username' && $p->{$f} && $p->{$f} !=$c) $p->{$f} .= ' ;<br> '  . $c;
				else $p->{$f} = $c;

			}

			unset($f, $c, $q_ok, $qid);
		}

		if($p->mastodon_id==1) $p->status = 'probation';

		if($p->status=='probation') $p->status_class = 'info';
		elseif($p->status=='invite') $p->status_class = 'warning';
		elseif($p->status=='full') $p->status_class = 'success';

	}

    $pagination = new Pagination($paginator, $app['url_generator'], 'admin/members', $sort_by, $sorting);

    return $app['twig']->render('table.html.twig', array(
        'items' => $people,
        'pagination' => $pagination
    ));
	}
})
->value('page', 1)
->value('sort_by', 'ts_started')
->value('sorting', 'desc')
->assert('page', '\d+') // Numbers only
->assert('sort_by','[a-zA-Z_]+') // Match a-z, A-Z, and "_"
->assert('sorting','(\basc\b)|(\bdesc\b)') // Match "asc" or "desc"
->bind('admin/members');
