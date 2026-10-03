<?php

namespace App\Form;

use App\Entity\ControversyItem;
use App\Entity\Source;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ControversyItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label' => false,
                'widget' => 'single_text',
            ])
            ->add('title', TextType::class, [
                'label' => false,
            ])
            ->add('sources', CollectionType::class, [
                'entry_type' => SourceType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype_name' => '__source__',
                'prototype_data' => (new Source())->setIsVisible(true),
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
                $item = $event->getData();
                if (!$item instanceof ControversyItem) {
                    return;
                }

                if (null === $item->getType() || '' === $item->getType()) {
                    $item->setType('fait');
                }

                if (null === $item->isVisible()) {
                    $item->setIsVisible(true);
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ControversyItem::class,
        ]);
    }
}
