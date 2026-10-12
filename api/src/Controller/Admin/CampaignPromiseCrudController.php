<?php

namespace App\Controller\Admin;

use App\Entity\CampaignPromise;
use App\Entity\Source;
use App\Form\SourceType;
use App\Service\UploadedImageField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<CampaignPromise>
 */
class CampaignPromiseCrudController extends AbstractCrudController
{
    public function __construct(private readonly UploadedImageField $images)
    {
    }

    public static function getEntityFqcn(): string
    {
        return CampaignPromise::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Promesse')
            ->setEntityLabelInPlural('Promesses')
            ->setDefaultSort(['title' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Titre');
        yield TextareaField::new('quote', 'Citation')->hideOnIndex();
        yield ChoiceField::new('status', 'Statut');
        yield ChoiceField::new('resultType', 'Chiffre')
            ->setRequired(false)
            ->hideOnIndex();
        yield NumberField::new('resultValue', 'Valeur atteinte')
            ->setRequired(false)
            ->setNumDecimals(2)
            ->hideOnIndex();
        yield NumberField::new('objective', 'Objectif')
            ->setRequired(false)
            ->setNumDecimals(2)
            ->setHelp('Seulement pour un nombre. L’objectif d’un pourcentage est 100.')
            ->hideOnIndex();
        yield TextField::new('resultLabel', 'Libellé')
            ->setRequired(false)
            ->setFormTypeOption('empty_data', null)
            ->setHelp('Par exemple « logements » ou « € ». Seulement pour un nombre.')
            ->hideOnIndex();
        yield BooleanField::new('isVisible', 'Visible');
        yield $this->images->illustration()->setRequired(false);
        yield AssociationField::new('theme', 'Thèmes');
        yield AssociationField::new('sources', 'Articles')->hideOnForm();
        yield CollectionField::new('sources', 'Articles')
            ->setEntryType(SourceType::class)
            ->allowAdd()
            ->allowDelete()
            ->setEntryIsComplex()
            ->renderExpanded()
            ->setFormTypeOption('by_reference', false)
            ->setFormTypeOption('prototype_data', (new Source())->setIsVisible(true))
            ->onlyOnForms();
    }
}
