<?php

namespace App\Form;

use App\Entity\Faq;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FaqType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', TextareaType::class,[
                'attr'=>['class'=>'textfield'],
            ])
            // ->add('answer', TextareaType::class,[
            //     'attr'=>[
            //         'class'=>'textfield'
            //         ],
            // ])
            ->add('answer', TextareaType::class, array('required' => false, 'attr' =>array('class' =>'textfield')))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Faq::class,
        ]);
    }
}
