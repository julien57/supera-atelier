<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\EventType;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Event $event */
        $event = $options['data'];

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
        ;

        if ($options['enterpriseField'] && $options['enterpriseField'] === true) {
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
            'enterpriseField' => 'enterpriseField'
        ]);
    }
}
