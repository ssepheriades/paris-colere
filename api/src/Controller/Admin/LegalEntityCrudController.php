<?php

namespace App\Controller\Admin;

use App\Entity\LegalEntity;
use App\Service\UploadedImageField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<LegalEntity>
 */
class LegalEntityCrudController extends AbstractCrudController
{
    public function __construct(private readonly UploadedImageField $images)
    {
    }

    public static function getEntityFqcn(): string
    {
        return LegalEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Structure')
            ->setEntityLabelInPlural('Structures')
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom');
        yield $this->images->logo();
        yield ChoiceField::new('type', 'Type');
        yield TextField::new('shortDescription', 'Description courte');
        yield TextField::new('website', 'Site web');
        yield AssociationField::new('personLegalEntities', 'Fonctions')->onlyOnIndex();
    }
}
