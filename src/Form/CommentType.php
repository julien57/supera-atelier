<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Commentaire'
            ])
            ->add('noteUser', ChoiceType::class, [
                'label' => 'Note *',
                'choices' => ['1 (Très basse)' => 1, '2' => 2, '3' => 3, '4' => 4, '5 (moyenne)' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, '10 (au top)' => 10
                ],
                'data' => 5
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
