<?php

namespace App\Form;

use App\Entity\LegalEntity;
use App\Entity\PersonLegalEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonLegalEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('legalEntity', EntityType::class, [
                'class' => LegalEntity::class,
                'label' => 'Structure',
                'placeholder' => '',
            ])
            ->add('position', TextType::class, [
                'label' => 'Fonction',
                'required' => false,
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Début',
                'widget' => 'single_text',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Fin',
                'widget' => 'single_text',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PersonLegalEntity::class,
        ]);
    }
}
