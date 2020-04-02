<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;

class AmountGiftType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('amount', ChoiceType::class, [
                'label' => 'Sélectionner un montant',
                'choices' => [
                    'Sélectionner' => null,
                    '30 €' => 30,
                    '50 €' => 50,
                    '70 €' => 70,
                    '100 €' => 100,
                    '150 €' => 150,
                    '200 €' => 200,
                ]
            ])
            ->add('choiceAmount', IntegerType::class, [
                'label' => 'Choisir un montant (25€ minimum)',
                'constraints' => [
                    new GreaterThan(['value' => 25, 'message' => 'Le montant doit être supérieur à 25€.'])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
