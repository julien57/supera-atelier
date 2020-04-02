<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\EventType;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventSpaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'atelier'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description'
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix / personne'
            ])
            ->add('nbMembers', NumberType::class, [
                'label' => 'Nombre de personnes maximum'
            ])
            ->add('formatorName', TextType::class, [
                'label' => 'Nom du formateur'
            ])
            ->add('eventType', EntityType::class, [
                'class' => EventType::class,
                'choice_label' => 'name',
                'label' => 'Catégorie'
            ])
            ->add('photos', CollectionType::class, [
                'label' => false,
                'entry_type' => PhotoType::class,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('workshopDates', CollectionType::class, [
                'label' => false,
                'entry_type' => WorkShopType::class,
                'allow_add' => true,
                'allow_delete' => true,
            ])
        ;

        if (isset($options['entrerpriseField']) && $options['entrerpriseField']) {
            $builder
                ->add('user', EntityType::class, [
                    'class' => User::class,
                    'choice_label' => function (User $user) {
                        return $user->getDisplayName();
                    },
                    'label' => 'Entreprise'
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
