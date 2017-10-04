<?php
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;

$app['my.formFactory'] = Forms::createFormFactoryBuilder()
    ->addExtension(new ValidatorExtension(Validation::createValidator()))
    ->addExtension(new HttpFoundationExtension())
    ->getFormFactory()
;


$app->match('/build/', function (Request $request) use ($app) {


	$bv->page_title = "Questionnaires";

	$data = questionnaires();

	return $app['twig']->render('dash-list.html.twig', array('data' => $data, 'title' => $bv->page_title) );

});




$app->match('/build/questionnaire', function (Request $request) use ($app) {

	global $bv, $choices, $choice_link;


	if(!isset($_GET['new'])){

		$bv->page_title = "Edit Questionnaire";

		$bv->questionnaire_id = $_GET['id'] ? $_GET['id'] : $app['session']->get('questionnaire'); // get from session 

		if($bv->questionnaire_id) $bv->questionnaire = questionnaire_get($bv->questionnaire_id);

	}

	if(!$bv->questionnaire->id){

		$bv->page_title = "New Questionnaire";

	}

	$app['session']->set('questionnaire', $bv->questionnaire->id);



	$form_builder = $app['my.formFactory']->createBuilder(FormType::class, $bv->questionnaire);

	$form_builder->add('questionnaire_title', null, [
		'label' => 'Title',
		'attr'=>['placeholder' => 'User Survey'],
	])
	->add('questionnaire_name', null, [
		'label' => 'Short name',
		'attr'=>['placeholder' => 'survey'],
	]);

	if($bv->questionnaire->id) $output_code .= '
	<a href="/build/question" id="skip" class="btn-sm btn btn-info float-right">Add Question</a>
	<label class="font-weight-bold">Questions</label>
	';



	$choice_link = '/question?id=';

	$choices = [];
	$questions = questionnaire_questions($bv->questionnaire->id);

	foreach ($questions as $s) {
		//print_r($s);
		$choices[$s->id] = $s->question_text;
	}

	if($choices){
		$attr['style'] .= 'display:none;';

		$output_code .= get_include($bv->base_path.'/templates/sortable.html');

		if($bv->questionnaire->id) $output_code .= '
		<a href="/build/question" id="skip" class="btn-sm btn btn-info float-right">Add Question</a>
		';

		$app['session']->set('num_steps', count($choices));
	}



	$form = $form_builder->getForm();

	$form->handleRequest($request);

	$is_valid = $form->isValid(); // regular form validates OK

	if(!$is_valid) $data = (array) json_decode(file_get_contents("php://input"));
	else $data = $form->getData(); // regular form POST

	if ($data) {

		error_log(print_r($data, true));

		if($data['sortable']){ // user is sorting the questions

			$ord=1;
			foreach ($data['sortable'] as $so) {
				//print_r($so);
				if($so->id){

					$question = question_get($so->id);

					if($question){

						$question->step = $ord;

						R::store( $question );

						$ord++;
					}
				}
			}
			exit('sorted '.$ord);
		}

		// do something with the data
		$bv->item = $bv->questionnaire;
		$id = item_save('questionnaire', $data);
		//print_r($id);

		$app['session']->set('questionnaire', $id);

		// redirect somewhere
		return $app->redirect('/build/question');
	}

	// display the form
	return $app['twig']->render('form.html.twig', array('form' => $form->createView(), 'output_code' => $output_code, 'title' => $bv->page_title));
});




$app->match('/build/question', function (Request $request) use ($app) {

	global $bv;

	$bv->questionnaire_id = $_GET['questionnaire'] ? $_GET['questionnaire'] : $app['session']->get('questionnaire'); // get from session

	if($bv->questionnaire_id) $bv->questionnaire = questionnaire_get($bv->questionnaire_id);

	if(!$bv->questionnaire->id) return $app->redirect('/build/');

	$app['session']->set('questionnaire', $bv->questionnaire->id);

	//$app['monolog']->debug('Testing the Monolog logging.');

	$bv->answer_types = [
		'ShortText'=>'Text (short)',
		'LongText'=>'Text (long)',
		'Choice'=>'Choice from list',
		'MultipleChoices'=>'Multiple choices from list',
		'Email'=>'Email address',
		'Phone'=>'Phone number',
		'URL'=>'Webpage / URL',
		'MapLocation'=>'Map / GPS Location',
		'Language'=>'Language',
		'Country'=>'Country',
		'Timezone'=>'Timezone',
		'Date'=>'Date',
		'DateTime'=>'Date & Time',
		'Time'=>'Time',
		'Birthday'=>'Birthdate',
		'Number'=>'Number (decimals possible)',
		'NumberInteger'=>'Number (without decimals)',
		'Percentage'=>'Percentage',
		'Currency'=>'Currency',
		'Price'=>'Price (Number & Currency)',
		'Password'=>'Password',
		'UploadImage'=>'Upload Image',
		'UploadDoc'=>'Upload Document',
		'UploadFile'=>'Upload Any File',
		'Sortable'=>'Sortable List',
	];

	$bv->answer_examples = [
	'NumberInteger'=>1,
	'LongText'=>2,
	'Country'=>3,
	'Choice'=>4,
	'MultipleChoices'=>5,
	'Email'=>6,
	'Timezone'=>7,
	'Phone'=>8,
	'URL'=>9,
	'Date'=>10,
	'DateTime'=>11,
	'Time'=>12,
	'Currency'=>13,
	'Price'=>14,
	'Password'=>15,
	'Number'=>16,
	'Percentage'=>17,
	'Birthday'=>18,
	'UploadImage'=>19,
	'MapLocation'=>20,
	'Sortable'=>21,

	'ShortText'=>23,
	'Language'=>24
	];

	//$types = array_combine($types, $types);
	$types = array_flip($bv->answer_types);

	$form = $app['my.formFactory']->createBuilder(FormType::class, $data)
		->add('answer_type', ChoiceType::class, array(
			'label' => 'What kind of information do you need?',
			'choices' => $types,
			'expanded' => true,
		    'choice_label' => function ($value, $key, $index) {
		    	global $bv;
		    	//var_dump($bv->answer_examples, $value);
		       if($bv->answer_examples[$value]) return $key.'<a href="/question?id='.$bv->answer_examples[$value].'" target="_blank" class="btn btn-sm float-right"><i class="fa fa-eye" aria-hidden="true"></i></a>';
		        return $key;
		    },
		))
		->add('question_text', null, [
			'label' => 'Ask the question',
			'attr'=>['placeholder' => 'How old are you?'],
		])
		->add('question_name', null, [
			'label' => 'Field name',
			'attr'=>['placeholder' => 'age'],
		])
		->add('question_note', null, [
			'label' => 'Note (optional)',
			'attr'=>['placeholder' => 'All ages welcome!'],
		])
	   ->add('answer', CollectionType::class, array(
		   // each entry in the array will be an "email" field
			'label' => 'For OneChoice, MultiChoices, ListChoice, Sortable –  add possible answers:',
		   'entry_type'   => TextType::class,
		   'allow_add'	=> true,
		   // these options are passed to each "email" type
		   'entry_options'  => array(
			   'label'	  => false,
			   'required'	  => false,
			   'attr'	  => array('class' => ' extra-input')
		   ),
	   ))
//		->add('save', SubmitType::class, [
//			'label' => 'Save',
//		])
		->getForm();

	$form->handleRequest($request);

	if ($form->isValid()) {
		$data = $form->getData();

		$data['questionnaire'] = $bv->questionnaire;
		$data['step'] = $app['session']->get('num_steps')+1; // increment

		// do something with the data
		$id = item_save('question', $data);
		//print_r($id);
//
//		$show = $app['twig']->render('data.html.twig', array('data' => $bv->item) );
//
//		//email_send($show);
//
//		return $show;

		// redirect somewhere
		return $app->redirect('/build/questionnaire?id='.$bv->questionnaire->id);
	}

	// display the form
	return $app['twig']->render('build_question.html.twig', array('form' => $form->createView(), 'title' => $bv->questionnaire->questionnaire_title ));
});



$app->match('/build/action', function (Request $request) use ($app) {

	global $bv;

	$bv->questionnaire_id = $_GET['questionnaire'] ? $_GET['questionnaire'] : $app['session']->get('questionnaire'); // get from session

	if($bv->questionnaire_id) $bv->questionnaire = questionnaire_get($bv->questionnaire_id);

	if(!$bv->questionnaire->id) return $app->redirect('/build/');

	$app['session']->set('questionnaire', $bv->questionnaire->id);

	//$app['monolog']->debug('Testing the Monolog logging.');

	$bv->answer_types = [
		'Message'=>'Text (short)',
		'Redirect'=>'Text (long)',
		'Email'=>'Choice from list',

	];

	$bv->answer_examples = [
	'NumberInteger'=>1,
	'LongText'=>2,
	'Country'=>3,
	'Choice'=>4,
	'MultipleChoices'=>5,
	'Email'=>6,
	'Timezone'=>7,
	'Phone'=>8,
	'URL'=>9,
	'Date'=>10,
	'DateTime'=>11,
	'Time'=>12,
	'Currency'=>13,
	'Price'=>14,
	'Password'=>15,
	'Number'=>16,
	'Percentage'=>17,
	'Birthday'=>18,
	'UploadImage'=>19,
	'MapLocation'=>20,
	'Sortable'=>21,

	'ShortText'=>23,
	'Language'=>24
	];

	//$types = array_combine($types, $types);
	$types = array_flip($bv->answer_types);

	$form = $app['my.formFactory']->createBuilder(FormType::class, $data)
		->add('answer_type', ChoiceType::class, array(
			'label' => 'What kind of information do you need?',
			'choices' => $types,
			'expanded' => true,
		    'choice_label' => function ($value, $key, $index) {
		    	global $bv;
		    	//var_dump($bv->answer_examples, $value);
		       if($bv->answer_examples[$value]) return $key.'<a href="/question?id='.$bv->answer_examples[$value].'" target="_blank" class="btn btn-sm float-right"><i class="fa fa-eye" aria-hidden="true"></i></a>';
		        return $key;
		    },
		))
		->add('question_text', null, [
			'label' => 'Ask the question',
			'attr'=>['placeholder' => 'How old are you?'],
		])
		->add('question_name', null, [
			'label' => 'Field name',
			'attr'=>['placeholder' => 'age'],
		])
		->add('question_note', null, [
			'label' => 'Note (optional)',
			'attr'=>['placeholder' => 'All ages welcome!'],
		])
	   ->add('answer', CollectionType::class, array(
		   // each entry in the array will be an "email" field
			'label' => 'For OneChoice, MultiChoices, ListChoice, Sortable –  add possible answers:',
		   'entry_type'   => TextType::class,
		   'allow_add'	=> true,
		   // these options are passed to each "email" type
		   'entry_options'  => array(
			   'label'	  => false,
			   'required'	  => false,
			   'attr'	  => array('class' => ' extra-input')
		   ),
	   ))
//		->add('save', SubmitType::class, [
//			'label' => 'Save',
//		])
		->getForm();

	$form->handleRequest($request);

	if ($form->isValid()) {
		$data = $form->getData();

		$data['questionnaire'] = $bv->questionnaire;
		$data['step'] = $app['session']->get('num_steps')+1; // increment

		// do something with the data
		$id = item_save('question', $data);
		//print_r($id);
//
//		$show = $app['twig']->render('data.html.twig', array('data' => $bv->item) );
//
//		//email_send($show);
//
//		return $show;

		// redirect somewhere
		return $app->redirect('/build/questionnaire?id='.$bv->questionnaire->id);
	}

	// display the form
	return $app['twig']->render('build_question.html.twig', array('form' => $form->createView(), 'title' => $bv->questionnaire->questionnaire_title ));
});
