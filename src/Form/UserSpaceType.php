<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSpaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isEnterprise = $options['fieldAdress'];
        /** @var User $user */
        $user = $options['data'];

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse mail *'
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom *'
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom *'
            ])
            ->add('phone', TelType::class, [
                'label' => 'N° de téléphone',
                'required' => false
            ])
            ->add('website', UrlType::class, [
                'label' => 'Lien de votre site',
                'required' => false
            ])
        ;

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
