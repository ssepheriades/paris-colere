<?php

namespace App\Form;

use App\Entity\KeyFigure;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KeyFigureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('figure', TextType::class, [
                'label' => false,
            ])
            ->add('label', TextType::class, [
                'label' => false,
            ])
            ->add('subLabel', TextType::class, [
                'label' => false,
                'required' => false,
                'empty_data' => null,
            ])
            ->add('priority', IntegerType::class, [
                'label' => false,
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('isVisible', CheckboxType::class, [
                'label' => false,
                'required' => false,
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
                $keyFigure = $event->getData();
                if (!$keyFigure instanceof KeyFigure) {
                    return;
                }

                if (null === $keyFigure->getPriority()) {
                    $keyFigure->setPriority(0);
                }

                if (null === $keyFigure->isVisible()) {
                    $keyFigure->setIsVisible(true);
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KeyFigure::class,
        ]);
    }
}
