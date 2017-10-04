<?php

function item_save($table_name = 'item', $data = [], $custom_linked_items=false) { // save object in DB, with support for many to many for items with array of data
	global $bv;

	//print_r($data); 

	if(!$bv->item) $bv->item = R::dispense( $table_name );

	//if(!is_array($data)) $data = [$data]; // make sure we are dealing with an array

	foreach ($data as $key => $value) {

		if(is_array($value)){ // multiple items - use linked table

			if($custom_linked_items){
				// assume we're already getting Redbean objects

				$linked_ref = 'shared'.ucwords($custom_linked_items).'List';

				$bv->item->{$linked_ref} = $value; // store relation


			} else {

				$linked_ref = 'shared'.ucwords($key).'List';

				foreach ($value as $linked_value) { // sub-array
				if($linked_value){
					$linked_item = R::dispense( $key ); // init linked table
					$linked_item->$key = $linked_value;
					R::store( $linked_item );

					$bv->item->{$linked_ref}[] = $linked_item; // store relation
				}}
			}



		}
		else $bv->item->$key = $value; // standard field

	}

	return R::store( $bv->item );
}


function respondent_question_responses_save($data, $col_prefix = "the") { // save object in DB, with support for many to many for items with array of data
	global $bv;
	//print_r([$bv->respondent, $bv->question, $data, $bv->response]);

	$bv->field_name = ($bv->question->question_name ? $bv->question->question_name : $bv->question->id);
	$bv->answer_type = ($bv->question->answer_type);

	//$bv->response = R::dispense( $bv->table_response ); // init response

	foreach ($data as $key => $value) {

		if($bv->answer_type=='Sortable'){ // dealing with an ajax list-sorting
			$ord=0;

			if(is_array($value)){
				foreach ($value as $so) {
					//print_r($so);
					if($so->id){

						$answer = answer_prepare($so->id, true);

						if($answer){

							if($ord>0) unset( $bv->response );  // create an additional response row

							$respond[$col_prefix.'Num'] = $ord;

							$response_ids[] = answer_response_save( $answer, $respond );

							$ord++;
						}
					}
				}
				exit('sorted '.$ord);
			} else {
				return true;
			}
		} elseif($key == $bv->field_name){ // dealing with the response to current question

			if($value){

				if(in_array($bv->answer_type, ['UploadImage','UploadDoc','UploadFile'])){

					$dir = __DIR__.'/../web/uploads';
					$file = $value;

					// compute a random name and try to guess the extension (more secure)
					$extension = $file->guessExtension();
					if (!$extension) {
					    // extension cannot be guessed
					    $extension = 'bin';
					}

					$filename = $bv->respondent->id.'_'.rand(1, 99999).'.'.$extension;

					$file->move($dir, $filename);

					$value = $filename;
					$col_name = 'Var';

				} elseif($bv->answer_type=='Email'){ // save email in main 'respondent' table
					$bv->respondent->email = $value;
					R::store( $bv->respondent );
					$col_name = 'Var';
				}
				elseif($bv->answer_type=='MapLocation'){
					$col_name = 'Point';
					$geo_col = $bv->table_response.'.'.$col_prefix.'_'.'point';
					R::bindFunc( 'read', $geo_col, 'asText' );
					R::bindFunc( 'write', $geo_col, 'GeomFromText' );
					$point_num = str_replace(',', ' ', $value);
					$value = "POINT($point_num)";
				}
				elseif($bv->answer_type=='Currency'){
					currency_set($value); // save selected currency in session
				}
				elseif(in_array($bv->answer_type, ['MultipleChoices','Choice'])){ // form can submit IDs of answers rather than contents
					$try_by_id=true;
				}
				elseif ($value instanceof DateTime) { // Date, DateTime, or Time
				  $date = $value->format('Y-m-d');
				  $time = $value->format('H:i:s');
				  if($date=='1970-01-01'){
				  	$col_name = 'Time';
				  	$value = $time;
				  } elseif($time=='00:00:00'){
				  	$col_name = 'Date';
				  	$value = $date;
				  } else {
					$col_name = 'DateTime';
				  	$value = $value->format('Y-m-d H:i:s');
				  }
				} elseif(in_array($bv->answer_type, ['Phone','LongText'])){
					$col_name = 'Var';
				} elseif($bv->answer_type=='Password'){
					$col_name = 'Var';
					// TODO
				} elseif(is_numeric($value)){
					$col_name = 'Num';
				}

				if($col_name && $col_prefix) $col_name = $col_prefix.$col_name;

				if($bv->answer_type=='Price'){ // both number & currency

					currency_get(); // load existing cookie
					currency_set($data['currency']); // set new cookie if selected

					$respond[$col_name] = $value; // Price amount

					$answer = answer_prepare($bv->currency); // get currency ID

					$response_ids[] = answer_response_save( $answer, $respond ); // save Price & currency ID

					response_save_custom($value); // amount
					response_save_custom($bv->currency, 'currency'); // currency code

				} elseif($col_name && !$try_by_id){ // store in appropriate column of response table

					$respond[$col_name] = $value; // store

					$response_ids[] = response_save($respond);

					response_save_custom($value);


				} elseif(is_array($value)) { // store (multiple answers) in answer table

					$try_by_id=true;
					$multii=0;

					foreach ($value as $linked_value) {

						if($linked_value){

							$answer = answer_prepare($linked_value, $try_by_id);

							if($answer){

								if($multii>0) unset($bv->response);  // so an additional response row gets created

								$response_ids[] = answer_response_save( $answer );

								$answers[] = $answer;

								$multii++;
							}
						}
					}

					response_save_custom($answers);


				} else { // store (single answer) in answer table

					if($value) $answer = answer_prepare($value, $try_by_id);

					if($answer){
						$response_ids[] = answer_response_save( $answer );
						response_save_custom($answer);
					}
				}

			} // end if value

		} elseif($bv->answer_type=='Price' && $key=='currency'){

			// already dealt with above

		} elseif($value) { // other regular field

			$respond[$key] = $value; // store
			$response_ids[] = response_save($respond);
			//response_save_custom($value);
		}

	} // end foreach

	//exit();
	return $response_ids;
}


function answer_prepare($value, $try_by_id=false) {
	global $bv;

	if($try_by_id) $answer = answer_get($value); // find pre-existing answer by ID

	if(!$answer->id){

		$answer = answer_find($value); // find pre-existing answer by content

		//print_r($value, $answer);

		if(!$answer->id){ // create new answer

			$answer = R::dispense( 'answer' );

			$answer->answer = $value;
			$answer->ts_added = R::isoDateTime();
			if($bv->respondent) $answer->by_respondent = $bv->respondent;

			R::store( $answer );
		}
	}

	return $answer;
}


function answer_response_save( $answer, $respond=[] ) {
	global $bv;

	$respond['answer'] = $answer; // store relation

	return response_save($respond);
}

function response_save($respond) {
	global $bv;

	try {

		if($respond['answer']){ // let's modify a past answer

			error_log("let's modify past answer: ".$respond['answer']->id);

			$find = [ 'respondent_id' => $bv->respondent->id, 'question_id' => $bv->question->id, 'answer_id' => $respond['answer']->id ];
			$bv->response = R::findOrCreate($bv->table_response, $find);

		} else {

			$bv->response = R::dispense( $bv->table_response ); // init new response
		}


		//var_dump($respond, $find, $bv->response); exit();

		$bv->response->respondent = $bv->respondent; // ownership
		$bv->response->question = $bv->question; // link to question

		$bv->response->response_ts = R::isoDateTime();

		$bv->response->import( $respond );

		//
		//$bv->response->setMeta("buildcommand.unique", array(array('respondent', 'question', 'answer')));


		$id = R::store( $bv->response ); // save

		//exit($id);
		return $id;

	} catch(Exception $e) {

	    error_log("Could not save a response (probably duplicate)");
	    // TODO

	}
}

function response_save_custom($data, $custom_field_name=false) {
	global $bv;

	try {

		return; // TODO! need to figure out respondent as unique ID

		if($data && $bv->questionnaire->questionnaire_name && ($custom_field_name || $bv->field_name)){ // also save to dedicated table for current questionnaire

			$bv->item = data_by_respondent($bv->questionnaire->questionnaire_name, $bv->respondent->id);
			//var_dump($bv->item);

//			$find_t = [ 'respondent' => $bv->respondent ];
//			$bv->item = R::findOrCreate($bv->questionnaire->questionnaire_name, $find_t);

			$data_a[($custom_field_name ? $custom_field_name : $bv->field_name)] = $data;
			$data_a['respondent'] = $bv->respondent;
			$data_a['updated_ts'] = $bv->response->response_ts;

			return item_save($bv->questionnaire->questionnaire_name, $data_a, 'answer');
		}

		//exit($id);
		return $id;

	} catch(Exception $e) {

	    error_log("Could not save a response to custom table (probably duplicate)");
	    // TODO

	}
}


function currency_get() {
	global $app, $bv;
	$bv->currency = $app['session']->get('currency');
	return $bv->currency;
}

function currency_set($currency) {
	global $app, $bv;
	if($currency){
		$bv->currency = $currency;
		$app['session']->set('currency', $currency); // save
	}
}

function data_by_id($table, $id) {
	return R::load( $table, $id );
}

function questionnaires() {
	return R::find( 'questionnaire');
}

function questionnaire_get($id) {
	return data_by_id( 'questionnaire', $id );
}

function questionnaire_questions($id) {
	return R::find( 'question', ' questionnaire_id = ? ORDER BY step ASC ', [ $id ]  );
}

function question_get($id) {
	return data_by_id( 'question', $id );
}

function answer_get($id) {
	return data_by_id( 'answer', $id );
}

function answer_find($value) {
	return R::findOne( 'answer', ' answer LIKE ? ', [ $value ]  );
}

function data_by_respondent($table, $respondent_id) {
	return R::findOne( $table, ' respondent_id = ? ORDER BY response_ts DESC ', [ $respondent_id ]  );
}

function response_by_question_id($question_id, $respondent_id) {
	global $bv;

	return R::findOne( $bv->table_response, ' question_id = ? AND  respondent_id = ? ORDER BY response_ts DESC ', [ $question_id, $respondent_id ]  );
}


function respondents_by_status($status) {
	global $bv;
	return R::find( $bv->table_respondent, ' status = ? ', [ $status ]  );
}

function a_respondent_by_status($status) {
	global $bv;
	return R::findOne( $bv->table_respondent, ' status = ? ', [ $status ]  );
}
