<?php
namespace  Mayel\FormExtrasBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
// use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class CustomcodeType extends AbstractType
{

	public function configureOptions(OptionsResolver $resolver)
  {
      $resolver->setDefaults(array(
				// 'label' => '<!-- custom HTML field type -->',
        // 'html' => '<!-- custom HTML field type -->',
      ));
			// $resolver->setRequired(array('html'));
  }

  public function getParent()
  {
      return HiddenType::class;
  }

	// public function getName()
 //  {
 //      return 'customcode';
 //  }
	// public function getBlockPrefix()
 // {
	// 	 return 'customcode';
 // }
}
