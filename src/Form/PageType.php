<?php

namespace App\Form;

use App\Entity\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('presentation', TextareaType::class, [
                'label' => 'Page "présentation"'
            ])
            ->add('valeurs', TextareaType::class, [
                'label' => 'Page "valeurs"'
            ])
            ->add('avantagesAtelier', TextareaType::class, [
                'label' => 'Page "avantages de proposer un atelier"'
            ])
            ->add('quipeutdeposer', TextareaType::class, [
                'label' => 'Page "qui peut déposer un atelier"'
            ])
            ->add('mentions', TextareaType::class, [
                'label' => 'Page "Mentions légales"'
            ])
            ->add('cgv', TextareaType::class, [
                'label' => 'Page "CGV"'
            ])
            ->add('cookies', TextareaType::class, [
                'label' => 'Page "CGV"'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
