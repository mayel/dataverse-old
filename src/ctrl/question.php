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

$app['my.formFactory'] = Forms::createFormFactoryBuilder()
    ->addExtension(new ValidatorExtension(Validation::createValidator()))
    ->addExtension(new HttpFoundationExtension())
    ->getFormFactory()
;

//use AdamQuaile\Bundle\FieldsetBundle\AdamQuaileFieldsetBundle;
//use Mayel\FormExtrasBundle\Form\Type\FieldsetType;


$app->match('/question', function (Request $request) use ($app) {
	global $bv;

	$output_code = '';

	// some default data for when the form is displayed the first time
	$data = array(
		'ts_latest' => R::isoDateTime(),
	);

	$bv->questionnaire_id = $_GET['questionnaire'] ? $_GET['questionnaire'] : $app['session']->get('questionnaire'); // get from session

	$bv->questionnaire = questionnaire_get($bv->questionnaire_id);

	if(!$bv->questionnaire->id && !$_GET['id']) exit('which questionnaire?');


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


	$bv->question_id = ($_GET['id'] ? $_GET['id'] : $app['session']->get('current_question')); // get from session

	if(isset($_GET['after']) || !$bv->question_id){ // forward

		$bv->from_step = isset($_GET['after']) ? $_GET['after'] : $app['session']->get('current_step'); // get from session

		$bv->question = R::findOne( 'question', ' questionnaire_id = ? AND step > ? ORDER BY step ASC LIMIT 1 ', [$bv->questionnaire->id, $bv->from_step] );

	} elseif(isset($_GET['before'])){ // backward

		$bv->from_step = isset($_GET['before']) ? $_GET['before'] : $app['session']->get('current_step'); // get from session

		$bv->question = R::findOne( 'question', ' questionnaire_id = ? AND step < ? ORDER BY step DESC LIMIT 1 ', [$bv->questionnaire->id, $bv->from_step] );

	}


	if(!$bv->question && is_numeric($bv->question_id)){

		$bv->question = R::load( 'question', $bv->question_id );

		$bv->questionnaire = $bv->question->questionnaire; // get from table

	}

	if(!$bv->question->id) {

		$bv->question = R::findOne( 'question', 'questionnaire_id = ?  ORDER BY step ASC LIMIT 1 ', [$bv->questionnaire->id] );
	}

	$bv->question_id = $bv->question->id;
	$bv->current_step = $bv->question->step;

	$app['session']->set('questionnaire', $bv->questionnaire->id); // save as session
	$app['session']->set('current_question', $bv->question_id); // save as session
	$app['session']->set('current_step', $bv->current_step); // save as session


	//var_dump($bv->questionnaire->id, $bv->respondent_id, $bv->question_id, $bv->question, $data, $bv->response);

	//$list_questions = R::findAll( 'question', ' ORDER BY id DESC LIMIT 10 ' );
	//R::preload($list_questions, array('answer')); // eager loading of answers

	//print_r($list_questions);

	$form_builder = $app['my.formFactory']->createBuilder(FormType::class, []);
	//var_dump($form_builder);
	// ($bv->response ? $bv->response : $data)

	//foreach ($list_questions as $bv->question) { 
		//print_r($bv->question);

		$field_label = $bv->question->question_text ? $bv->question->question_text : $bv->question->question_name;
		$field_name = ($bv->question->question_name ? $bv->question->question_name : $bv->question->id);
		//$field_name .= '-'. $bv->question->answer_type;

		$attr = array('class' => ' fieldtype-'.$bv->question->answer_type. ' field-'.$field_name);

		switch ($bv->question->answer_type) {
			case "LongText":

				$attr['rows'] = '4';

				$form_builder->add($field_name, TextareaType::class, array(
					'label' => $field_label,
					'attr'	  => $attr
				));

				break;
			case "Email":

				$form_builder->add($field_name, EmailType::class, array(
					'label' => $field_label,
					'attr'	  => $attr,
					'constraints' => array(new Assert\Email(array(
			            'message' => 'The email {{ value }} is not a valid email.',
			            'checkMX' => true,
			        )))
				));

				break;
			case "Date":

				$attr['style'] = 'max-width:240px';

				$form_builder->add($field_name, DateType::class, array(
					'label' => $field_label,
					'attr'	  => $attr,
					'widget' => 'single_text',
				));

				break;
			case "Time":

				$attr['style'] = 'max-width:140px';

				$form_builder->add($field_name, TimeType::class, array(
					'label' => $field_label,
					'attr'	  => $attr,
					'widget' => 'single_text',
				));

				break;
			case "DateTime":

				//$attr['style'] = 'width:50%';

				$form_builder->add($field_name, DateTimeType::class, array(
					'label' => $field_label,
					'attr'	  => $attr,
					'date_widget' => 'single_text',
					'time_widget' => 'single_text',
				));

				break;
			case "Birthday":

				$attr['style'] = 'max-width:240px';

				$form_builder->add($field_name, BirthdayType::class, array(
					'label' => $field_label,
					'attr'	  => $attr,
					'widget' => 'single_text',
				));

				break;
			case "NumberInteger":

				$attr['style'] = 'max-width:200px';

				$form_builder->add($field_name, IntegerType::class, array(
					'label' => $field_label,
					'scale' => 0,
					'attr'	  => $attr
				));

				break;
			case "Number":

				$attr['style'] = 'max-width:200px';

				$form_builder->add($field_name, NumberType::class, array(
					'label' => $field_label,
					//'scale' => 0,
					'attr'	  => $attr
				));

				break;
			case "Percentage":

				$attr['style'] = 'max-width:100px';

				$form_builder->add($field_name, PercentType::class, array(
					'label' => $field_label,
					//'scale' => 0,
					'attr'	  => $attr
				));

				break;
			case "Phone":

				$output_code .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/9.0.14/css/intlTelInput.css" />
				<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/9.0.14/js/intlTelInput.min.js"></script>';

				//$attr['class'] .= ' ';

				$form_builder->add($field_name, TextType::class, array(
					'label' => $field_label,
					//'choice_value' => '',
					//'placeholder' => '+1 555 123 4567',
					'attr'	  => $attr
				));

				break;
			case "Currency":

				currency_get();

				$attr['class'] .= ' select2';

				$form_builder->add($field_name, CurrencyType::class, array(
					'label' => $field_label,
					'placeholder' => 'Select a currency',
					'attr'	  => $attr,
					'data'	  => $bv->currency,
				));

				break;
			case "Price":

				currency_get();

				$param = array(
					'label' => $field_label,
					'attr'	  => $attr
				);

				//if($currency) $param['currency'] = $bv->currency;
				//$form_builder->add($field_name, MoneyType::class, $param);

				$form_builder->add($field_name, NumberType::class, $param);

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

				$form_builder->add($field_name, CountryType::class, array(
					'label' => $field_label,
					//'choice_value' => '',
					'placeholder' => 'Select a country',
					'attr'	  => $attr
				));

				break;
			case "Timezone":

				$attr['class'] .= ' select2';

				$form_builder->add($field_name, TimezoneType::class, array(
					'label' => $field_label,
					//'choice_value' => '',
					'placeholder' => 'Select a timezone',
					'attr'	  => $attr
				));

				break;
			case "Password":

				$output_code .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/hideshowpassword/2.0.10/hideShowPassword.min.js"></script><link rel="stylesheet" href="/css/pw.wink.css">';

				$form_builder->add($field_name, PasswordType::class, array(
					'label' => $field_label,
					//'choice_value' => '',
					//'placeholder' => 'Choose a password',
					'attr'	  => $attr
				));

//				$form_builder->add($field_name.'-check', PasswordType::class, array(
//					'label' => 'Type it again',
//					//'choice_value' => '',
//					//'placeholder' => 'Choose a password',
//					'attr'	  => $attr
//				));

				break;
			case "Choice":

				//$answers = R::findAll( 'answer', $bv->question);
				//print_r($answers);

				$choices = [];
				foreach ($bv->question->sharedAnswerList as $s) {
					//print_r($s);
					$choices[$s->answer] = $s->id;
				}

				//$attr['class'] .= ' select2';

				$form_builder->add($field_name, ChoiceType::class, array(
					'label' => $field_label,
					'choices' => $choices,
					'expanded' => true,
					'multiple' => false,
					'attr'	  => $attr,
					//'label_attr'	  => ['class'=>'btn btn-primary'],
					//'choice_attr'	  => ['class'=>'xyz']
				));

				break;

			case "MultipleChoices":

				//$answers = R::findAll( 'answer', $bv->question);
				//print_r($answers);

				$choices = [];
				foreach ($bv->question->sharedAnswerList as $s) {
					//print_r($s);
					$choices[$s->answer] = $s->id;
				}

				$form_builder->add($field_name, ChoiceType::class, array(
					'label' => $field_label,
					'choices' => $choices,
					'expanded' => true,
					'multiple' => true,
					'attr'	  => $attr
				));

				break;
			case "UploadImage":
			case "UploadDoc":
			case "UploadFile":

				$form_builder->add($field_name, FileType::class, array(
					'label' => $field_label,
					'attr'	  => $attr
				));

				break;
			case "URL":

				$form_builder->add($field_name, URLType::class, array(
					'label' => $field_label,
					'attr'	  => $attr,
					'data'	  => 'http://'
				));

				break;
			case "MapLocation":

				$attr['style'] .= 'display:none;';

				$output_code .= "<script>
				var loc_field = '#form_$field_name';
				</script>";

				$output_code .= file_get_contents($bv->base_path.'/templates/map.html');

				$form_builder->add($field_name, TextType::class, array(
					'label' => $field_label,
					'attr'	  => $attr,
				));

				break;
			case "Sortable":


				global $choices;
				$choices = [];
				foreach ($bv->question->sharedAnswerList as $s) {
					//print_r($s);
					$choices[$s->id] = $s->answer;
				}

				$attr['style'] .= 'display:none;';

				$output_code .= "<script>
				var field_name = '$field_name';
				</script>";

				$output_code .= get_include($bv->base_path.'templates/sortable.html');

				$form_builder->add($field_name, TextType::class, array(
					'label' => $field_label,
					'attr'	  => $attr,
					'data'	  => 'sorted',
				));

				break;

			default:

				if($field_name) $form_builder->add($field_name, TextType::class, array(
					'label' => $field_label,
					'attr'	  => $attr
				));

				break;
		}

//		$form_builder->add("answer_type", HiddenType::class, array(
//		    'data' => $bv->question->answer_type,
//		));

	//}

//	$form_builder->add('save', SubmitType::class, [
//			'label' => 'Continue',
//		]);

	$form = $form_builder->getForm();

	$form->handleRequest($request);

	$is_valid = $form->isValid(); // regular form validates OK

	if(!$is_valid) $data = (array) json_decode(file_get_contents("php://input"));
	else $data = $form->getData(); // regular form POST

	if ($data) {

		//error_log(print_r($data, true));

		//$data['ts_latest'] = R::isoDateTime();

		// do something with the data

		$response_ids = respondent_question_responses_save($data);

		return $app->redirect('?after='.$bv->current_step);

		//print_r($id);

		//$show = $app['twig']->render('data.html.twig', array('data' => $data) );


		//email_send($show);

		//return $show;

		// redirect somewhere
		//return $app->redirect('/');
	}

	// display the form
	return $app['twig']->render('question.html.twig', array('form' => $form->createView(), 'output_code' => $output_code, 'current_step' => $bv->current_step));
});
