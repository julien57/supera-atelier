<?php

namespace App\Form;

use App\Entity\EventType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EventType $eventType */
        $eventType = $options['data'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la catégorie'
            ])
            ->add('icon', FileType::class, [
                'label' => 'Téléchargement icône',
                'data_class' => null,
                'required' => $eventType->getIcon() ? false : true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EventType::class,
        ]);
    }
}
