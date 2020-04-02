<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isEnterprise = $options['fieldAdress'];

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse mail'
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom'
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom'
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les champs doivent être identiques.',
                'options' => ['attr' => ['class' => 'password-field']],
                'first_options'  => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Répétez mot de passe'],
                'empty_data' => ''
            ]);

        if ($isEnterprise) {
            $builder
                ->add('adress', TextType::class, [
                'label' => 'Adresse'
                ])
                ->add('zipCode', TextType::class, [
                    'label' => 'Code postal'
                ])
                ->add('city', TextType::class, [
                    'label' => 'Ville'
                ])
            ;
        }

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'fieldAdress' => 'fieldAdress'
        ]);
    }
}
