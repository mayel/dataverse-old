<?php
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpKernel\HttpKernelInterface;

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

$app->get('/pinfo', function () use ($app) {
    phpinfo();
})
;

$app->get('/adminZZZ', function () use ($app) {
    return "Auth OK";
})
;

// TEMP

$app->get('/admin/test', function () use ($app) {
    //return $app['twig']->render('index.html.twig', array());
    // rewrite to somewhere else
    $subRequest = Request::create('/test', 'GET');
    return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
})
;

$app->match('/test', function (Request $request) use ($app) {
    global $bv;

    user_init();
    //var_dump($bv->user);

    //user_create(null, 'test', 'mayel@deborniol.com');

    // some default data for when the form is displayed the first time
    //	$data = array(
    //		'name' => '',
    //		'email' => '',
    //	);

    //$app['monolog']->debug('Testing the Monolog logging.');


    $needs = array('Medical', 'Legal', 'Transport', 'Food', 'Clothes', 'Hygiene');
    $needs_choices = array_combine($needs, $needs);

    $places = array('Country', 'Camp', 'Dentention Center', 'Prison', 'Private House', 'Other', 'Will tell you later', 'GPS coordinates');
    $places_choices = array_combine($places, $places);

    $form = $app['my.formFactory']->createBuilder(FormType::class, $data)
        ->add('type_need', ChoiceType::class, array(
            'label' => "What kind of urgent need do you have?",
            'choices' => $needs_choices,
            'expanded' => true,
        ))
        ->add('name')
        ->add('age', IntegerType::class, array(
            'scale' => 0,
        ))
        ->add('frequent_internet_access', ChoiceType::class, array(
            'label' => "Do you have frequent access to internet?",
            'choices' => array('Yes' => 1, 'No' => 0),
            'expanded' => true,
        ))
        ->add('speaks_english', ChoiceType::class, array(
            'label' => "Do you speak english?",
            'choices' => array('Yes' => 1, 'No' => 0),
            'expanded' => true,
        ))
        ->add('translator_needed', ChoiceType::class, array(
            'label' => "Do you need a translator?",
            'choices' => array('Yes' => 1, 'No' => 0),
            'expanded' => true,
        ))
        ->add('language', ChoiceType::class, array(
            'label' => "What language?",
        ))
        ->add('email', EmailType::class)
        ->add('phone')
        ->add('facebook')
        ->add('location')
        ->add('kind_of_place', ChoiceType::class, array(
            'label' => "What kind of place?",
            'choices' => $places_choices,
            'expanded' => true,
        ))
        ->add('save', SubmitType::class, [
            'label' => 'Save',
        ])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isValid()) {
        $data = $form->getData();

        // do something with the data
        //print_r($data);

        $bv->item = R::dispense('request');

        $id = item_save('request', $data);

//
        //		foreach ($data as $key => $value) {
        //			$bv->item->$key = $value;
        //		}
        //		$id = R::store( $bv->item );

        //print_r($id);

        $show = $app['twig']->render('data.html.twig', array('data' => $bv->item, 'title' => $bv->page_title));


        //email_send($show);

        return $show;

        // redirect somewhere
        //return $app->redirect('/');
    }

    // display the form
    return $app['twig']->render('form.html.twig', array('form' => $form->createView(), 'output_code' => $output_code, 'title' => $bv->page_title));
});
