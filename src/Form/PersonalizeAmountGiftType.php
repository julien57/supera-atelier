<?php

namespace App\Form;

use App\Entity\GiftAmount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonalizeAmountGiftType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('forName', TextType::class, [
                'label' => 'Pour',
                'attr' => [
                    'placeholder' => 'Prénom du destinataire'
                ]
            ])
            ->add('ofName', TextType::class, [
                'label' => 'De la part de',
                'attr' => [
                    'placeholder' => 'Votre prénom'
                ]
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Votre message',
                'attr' => [
                    'placeholder' => 'Un petit mot doux...',
                    'rows' => 5
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GiftAmount::class
        ]);
    }
}
