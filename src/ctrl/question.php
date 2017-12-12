<?php
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
//use Symfony\Component\Form\Extension\Core\Type\LanguageType;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;

use Symfony\Component\Intl\Intl;

$app['my.formFactory'] = Forms::createFormFactoryBuilder()
    ->addExtension(new ValidatorExtension(Validation::createValidator()))
    ->addExtension(new HttpFoundationExtension())
    ->getFormFactory()
;

//use AdamQuaile\Bundle\FieldsetBundle\AdamQuaileFieldsetBundle;
//use Mayel\FormExtrasBundle\Form\Type\FieldsetType;

use Mayel\FormExtrasBundle\Form\Type\CustomcodeType;


$app->match('/question', function (Request $request) use ($app) {
	global $bv;

	// some default data for when the form is displayed the first time
	$data = array(
		'ts_latest' => R::isoDateTime(),
	);

	$bv->questionnaire_id = $_GET['questionnaire'] ? $_GET['questionnaire'] : $app['session']->get('questionnaire'); // get from session

	if($bv->questionnaire_id) $bv->questionnaire = questionnaire_get($bv->questionnaire_id);

	if(!$bv->questionnaire->id && !$_GET['id']) exit('which questionnaire are you trying to answer?');


	$bv->respondent_id = $app['session']->get('respondent');

	if ($bv->respondent_id) {

		$bv->respondent = R::load( $bv->table_respondent, $bv->respondent_id ); // load existing respondent
		$bv->respondent_id = $bv->respondent->id;

	}

	if(!$bv->respondent_id) {

		$data_respondent['ts_started'] = R::isoDateTime();

		$data_respondent['questionnaire'] = $bv->questionnaire;

		$data_respondent['ip'] = $request->server->get('REMOTE_ADDR');
		$data_respondent['browser'] = $_SERVER['HTTP_USER_AGENT'];

		$bv->respondent_id = item_save($bv->table_respondent, $data_respondent ); // init in DB
		if($bv->respondent_id) $bv->respondent = $bv->item; // the respondent RB object

	}

	if(!$bv->respondent_id) {
		exit('could not initialise respondent');
	} else {
		$app['session']->set('respondent', $bv->respondent_id); // save as session
	}

//	if(!$bv->response) {
//		$bv->response = R::load( $bv->table_response, $bv->respondent_id ); // load existing responses
//	}


	//$bv->response = R::dispense( $bv->table_response );


	// $bv->question_id = ($_GET['id'] ? $_GET['id'] : $app['session']->get('current_question')); // get from session
	$bv->question_id = $_GET['id'];

	if($bv->question_id){
		$bv->questions[0] = R::load( 'question', intval($bv->question_id) );

	} else { // forward

		if(is_numeric($_GET['after'])) $bv->current_step = $_GET['after']+1;
		elseif(is_numeric($_GET['before'])) $bv->current_step = $_GET['before']-1;
		else $bv->current_step = is_numeric($_GET['step']) ? $_GET['step'] : $app['session']->get('current_step'); // get from URL or session

    if(!$bv->current_step) $bv->current_step = 1; // default to 1st step

		// $bv->questions = R::findAll( 'question', ' questionnaire_id = ? AND step = ? ORDER BY step_order ASC ', [$bv->questionnaire->id, $bv->current_step] );
		// R::fancyDebug( TRUE );

		$steps = R::find( 'step', ' questionnaire_id = ? AND step = ? ORDER BY `order` ASC', [$bv->questionnaire->id, $bv->current_step] );

		foreach ($steps as $s){
			// var_dump($s, $s->question);
			$s->question->step = $s->step;
			$bv->questions[] = $s->question;
		}

	}

	if(!count($bv->questions))  $bv->questions = questionnaire_questions($bv->questionnaire->id); // fallback to load all questions

	// if(isset($_GET['after']) || !$bv->question_id){ // forward
  //
	// 	$bv->from_step = isset($_GET['after']) ? $_GET['after'] : $app['session']->get('current_step'); // get from session
  //   if(!$bv->from_step) $bv->from_step = 0; // default to 1st question
  //
	// 	$bv->question = R::findOne( 'question', ' questionnaire_id = ? AND step > ? ORDER BY step ASC LIMIT 1 ', [$bv->questionnaire->id, $bv->from_step] );
  //
	// } elseif(isset($_GET['before'])){ // backward
  //
	// 	$bv->from_step = isset($_GET['before']) ? $_GET['before'] : $app['session']->get('current_step'); // get from session
  //
	// 	$bv->question = R::findOne( 'question', ' questionnaire_id = ? AND step < ? ORDER BY step DESC LIMIT 1 ', [intval($bv->questionnaire->id), intval($bv->from_step)] );
  //
	// }


	// var_dump($bv->questionnaire->id, $bv->respondent_id, $bv->question_id, $bv->questions, $bv->question, $data, $bv->response);

	//$list_questions = R::findAll( 'question', ' ORDER BY id DESC LIMIT 10 ' );
	//R::preload($list_questions, array('answer')); // eager loading of answers

	//print_r($list_questions);

	$form_builder = $app['my.formFactory']->createBuilder(FormType::class );
	// [
	// 	'allow_extra_fields' => true,
	// 	'extra_fields_message' => 'This form should not contain extra fields!'
	// ]

	//var_dump($form_builder);
	// var_dump($bv->questions);

	// var_dump($bv->questions);
foreach ($bv->questions as $bv->question) {
		// print_r($bv->question);

		if(!$bv->questionnaire) $bv->questionnaire = $bv->question->questionnaire; // get from table

		$bv->question_id = $bv->question->id;
		$bv->current_step = $bv->question->step;

		$app['session']->set('questionnaire', $bv->questionnaire->id); // save as session
		$app['session']->set('current_question', $bv->question_id); // save as session
		$app['session']->set('current_step', $bv->current_step); // save as session

		$bv->field_label = $bv->question->question_text ? $bv->question->question_text : $bv->question->question_name;
		$bv->field_name = ($bv->question->question_name ? $bv->question->question_name : $bv->question->id);
		//$bv->field_name .= '-'. $bv->question->answer_type;

		$bv->questions_by_field[$bv->field_name] = $bv->question;

		if($bv->question->continue_label) $bv->questionnaire->continue_label = $bv->question->continue_label; // custom per-question button

		$attr = array('class' => ' fieldtype-'.$bv->question->answer_type. ' field-'.$bv->field_name);

		$attr['help'] = $bv->question->question_note;

		if($bv->respondent_id) $r = response_by_question_id($bv->question_id, $bv->respondent_id); // laod previous response
		$prev_response = $r->the_var;
		if(!$prev_response) $prev_response = $r->the_num;
		if(!$prev_response) $prev_response = $r->the_date;
		if(!$prev_response) $prev_response = $r->the_point;
		if(!$prev_response && $r->answer) $prev_response = $r->answer->answer;

		if($r->answer) $prev_answer_id = $r->answer->id;

		$attr['value'] = $prev_response ? $prev_response : $bv->question->question_default_answer;

		switch ($bv->question->answer_type) {
			case "LongText":

				$attr['rows'] = '4';

				$form_builder->add($bv->field_name, TextareaType::class, array(
					'label' => $bv->field_label,
					'attr'	  => $attr
				));

				break;
			case "Email":

				$form_builder->add($bv->field_name, EmailType::class, array(
					'label' => $bv->field_label,
					'attr'	  => $attr,
					'constraints' => array(new Assert\Email(array(
			            'message' => 'The email {{ value }} is not a valid email.',
			            'checkMX' => true,
			        )))
				));

				break;
			case "Date":

				$attr['style'] = 'max-width:240px';

				$form_builder->add($bv->field_name, DateType::class, array(
					'label' => $bv->field_label,
					'attr'	  => $attr,
					'widget' => 'single_text',
				));

				break;
			case "Time":

				$attr['style'] = 'max-width:140px';

				$form_builder->add($bv->field_name, TimeType::class, array(
					'label' => $bv->field_label,
					'attr'	  => $attr,
					'widget' => 'single_text',
				));

				break;
			case "DateTime":

				//$attr['style'] = 'width:50%';

				$form_builder->add($bv->field_name, DateTimeType::class, array(
					'label' => $bv->field_label,
					'attr'	  => $attr,
					'date_widget' => 'single_text',
					'time_widget' => 'single_text',
				));

				break;
			case "Birthday":

				$attr['style'] = 'max-width:240px';

				$form_builder->add($bv->field_name, BirthdayType::class, array(
					'label' => $bv->field_label,
					'attr'	  => $attr,
					'widget' => 'single_text',
				));

				break;
			case "NumberInteger":

				$attr['style'] = 'max-width:200px';

				$form_builder->add($bv->field_name, IntegerType::class, array(
					'label' => $bv->field_label,
					'scale' => 0,
					'attr'	  => $attr
				));

				break;
			case "Number":

				$attr['style'] = 'max-width:200px';

				$form_builder->add($bv->field_name, NumberType::class, array(
					'label' => $bv->field_label,
					//'scale' => 0,
					'attr'	  => $attr
				));

				break;
			case "Percentage":

				$attr['style'] = 'max-width:100px';

				$form_builder->add($bv->field_name, PercentType::class, array(
					'label' => $bv->field_label,
					//'scale' => 0,
					'attr'	  => $attr
				));

				break;
			case "Phone":

				$output_after .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/9.0.14/css/intlTelInput.css" />
				<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/9.0.14/js/intlTelInput.min.js"></script>';

				//$attr['class'] .= ' ';

				$form_builder->add($bv->field_name, TextType::class, array(
					'label' => $bv->field_label,
					//'choice_value' => '',
					//'placeholder' => '+1 555 123 4567',
					'attr'	  => $attr
				));

				break;
			case "Currency":

				currency_get();

				$attr['class'] .= ' select2';

				\Locale::setDefault('en');

				$choices = [];
				foreach ($bv->question->sharedAnswerList as $s) {
					//print_r($s);
					$cname = Intl::getCurrencyBundle()->getCurrencyName($s->answer);
					$choices[$cname ? $cname : $s->answer] = $s->answer;
				}

				if($attr['value']) $bv->currency = $attr['value'];

				$form_builder->add($bv->field_name, CurrencyType::class, array(
					'label' => $bv->field_label,
					'placeholder' => 'Select a currency',
					'attr'	  => $attr,
					'data'	  => $bv->currency,
					'choices'	  => $choices,
			));

				break;
			case "Price":

				currency_get();

				$param = array(
					'label' => $bv->field_label,
					'attr'	  => $attr
				);

				//if($currency) $param['currency'] = $bv->currency;
				//$form_builder->add($bv->field_name, MoneyType::class, $param);

				$form_builder->add($bv->field_name, NumberType::class, $param);

				$attr['class'] .= ' select2';

				$form_builder->add('currency', CurrencyType::class, array(
					'label' => 'In what currency?',
					'placeholder' => 'Select a currency',
					'attr'	  => $attr,
					'data'	  => $bv->currency,
				));

				break;
			case "Country":

				$attr['class'] .= ' select2';

				$form_builder->add($bv->field_name, CountryType::class, array(
					'label' => $bv->field_label,
					//'choice_value' => '',
					'placeholder' => 'Select a country',
					'attr'	  => $attr
				));

				break;
			case "Timezone":

				$attr['class'] .= ' select2';

				$form_builder->add($bv->field_name, TimezoneType::class, array(
					'label' => $bv->field_label,
					//'choice_value' => '',
					'placeholder' => 'Select a timezone',
					'attr'	  => $attr
				));

				break;
			case "Password":

				$output_after .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/hideshowpassword/2.0.10/hideShowPassword.min.js"></script><link rel="stylesheet" href="/css/pw.wink.css">';

				$form_builder->add($bv->field_name, PasswordType::class, array(
					'label' => $bv->field_label,
					//'placeholder' => 'Choose a password',
					'attr'	  => $attr
				));

//				$form_builder->add($bv->field_name.'-check', PasswordType::class, array(
//					'label' => 'Type it again',
//					//'placeholder' => 'Choose a password',
//					'attr'	  => $attr
//				));

				break;
			case "Dropdown":
				$dropdown=true;

			case "Choice":

				//$answers = R::findAll( 'answer', $bv->question);
				//print_r($answers);

				$choices = [];
				foreach ($bv->question->sharedAnswerList as $s) {
					//print_r($s);
					$choices[$s->answer] = $s->id;
				}

				if($dropdown) $attr['class'] .= ' select2';

				$data = $prev_answer_id;

				$form_builder->add($bv->field_name, ChoiceType::class, array(
					'label' => $bv->field_label,
					'choices' => $choices,
					'expanded' => !$dropdown,
					'multiple' => false,
					'attr'	  => $attr,
					'data'	  => $data,
					//'label_attr'	  => ['class'=>'btn btn-primary'],
					//'choice_attr'	  => ['class'=>'xyz']
				));
				unset($dropdown);

				break;
			case "MultipleChoices":

				//$answers = R::findAll( 'answer', $bv->question);
				//print_r($answers);

				$choices = [];
				foreach ($bv->question->sharedAnswerList as $s) {
					//print_r($s);
					$choices[$s->answer] = $s->id;
				}

				$form_builder->add($bv->field_name, ChoiceType::class, array(
					'label' => $bv->field_label,
					'choices' => $choices,
					'expanded' => true,
					'multiple' => true,
					'attr'	  => $attr
				));

				break;
			case "UploadImage":
			case "UploadDoc":
			case "UploadFile":

				$form_builder->add($bv->field_name, FileType::class, array(
					'label' => $bv->field_label,
					'attr'	  => $attr
				));

				break;
			case "URL":

				$form_builder->add($bv->field_name, URLType::class, array(
					'label' => $bv->field_label,
					'attr'	  => $attr,
					'data'	  => 'http://'
				));

				break;
			case "MapLocation":

				$attr['style'] .= 'display:none;';

				$output_after .= "<script>
				var loc_field = '#form_$bv->field_name';
				</script>";

				$output_before .= file_get_contents($bv->base_path.'/templates/map.html');

				$form_builder->add($bv->field_name, TextType::class, array(
					'label' => $bv->field_label,
					'attr'	  => $attr,
				));

				break;
			case "Sortable":


				global $sortable;
				$sortable->choices = [];
				foreach ($bv->question->sharedAnswerList as $s) {
					//print_r($s);
					$sortable->choices[$s->step][$s->id] = $s->answer;
				}

				$attr['style'] .= 'display:none;';

				$output_after .= "<script>
				var field_name = '$bv->field_name';
				</script>";

				$output_before .= get_include($bv->base_path.'templates/sortable.html');

				$form_builder->add($bv->field_name, TextType::class, array(
					'label' => $bv->field_label,
					'attr'	  => $attr,
					'data'	  => 'sorted',
				));

				break;
			case "Notice":

				$html = '<div class="form-group" id="'.$bv->field_name.'"><label class="form-control-label required">'.$bv->field_label.'</label>
				<p>'.$bv->question->question_note.'</div>';
				$attr['html'] = $html;

				$form_builder->add($bv->field_name, CustomcodeType::class, array(
					// 'label' => $bv->field_label,
					// 'data' => $bv->field_label,
					'attr'	  => $attr,
					// 'html'	  => $output_after
				));

				break;
			case "Include":

				$html = '<div class="form-group" id="'.$bv->field_name.'"><label class="form-control-label required" for="form_email">'.$bv->field_label.'</label>
				' . get_include($bv->base_path.'custom/'.$bv->field_name).'</div>';
				$attr['html'] = $html;

				$form_builder->add($bv->field_label, CustomcodeType::class, array(
					// 'label' => $bv->field_label,
					// 'data' => $bv->field_label,
					'attr'	  => $attr,
					// 'html'	  => $output_after
				));

				break;
			default:

				if($bv->field_name) $form_builder->add($bv->field_name, TextType::class, array(
					'label' => $bv->field_label,
					'attr'	  => $attr
				));

				break;
		}

//		$form_builder->add("answer_type", HiddenType::class, array(
//		    'data' => $bv->question->answer_type,
//		));

}

//	$form_builder->add('save', SubmitType::class, [
//			'label' => 'Continue',
//		]);

	$form = $form_builder->getForm();
	// error_log(print_r($form, true));

	$form->handleRequest($request);

	// error_log(print_r($_REQUEST, true));
	$is_valid = $form->isValid(); // regular form validates OK
	// error_log(print_r($form->isValid(), true));
	// error_log(print_r($form->getData(), true));

	if(!$is_valid) $data = (array) json_decode(file_get_contents("php://input")); // JSON from sortable
	else $data = $form->getData(); // regular form POST

	// error_log(print_r($data, true));

	if ($data) {

		//$data['ts_latest'] = R::isoDateTime();

		// do something with the data

		$response_ids = respondent_questions_responses_save($data);

		return $app->redirect('?after='.$bv->current_step);

		//print_r($id);

		//$show = $app['twig']->render('data.html.twig', array('data' => $data) ); // preview


		//email_send($show);

		//return $show;

		// redirect somewhere
		//return $app->redirect('/');

	} // end data


	$next_step = R::findOne( 'step', ' questionnaire_id = ? AND step > ? LIMIT 1 ', [$bv->questionnaire->id, $bv->current_step] );
	if($next_step->id) $bv->has_more_questions = true;

	// display the form
	return $app['twig']->render('question.html.twig', array('form' => $form->createView(), 'output_before' => $output_before, 'output_after' => $output_after, 'current_step' => $bv->current_step, 'has_more_questions' => $bv->has_more_questions , 'title' => $bv->questionnaire->questionnaire_title , 'continue_label' => ($bv->questionnaire->continue_label ? $bv->questionnaire->continue_label : 'Continue')));
});
