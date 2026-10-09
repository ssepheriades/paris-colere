<?php

namespace App\Controller\Admin;

use App\Entity\ControversyItem;
use App\Enum\ControversyItemType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<ControversyItem>
 */
class ControversyItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ControversyItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Fait')
            ->setEntityLabelInPlural('Faits')
            ->setDefaultSort(['date' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Titre');
        yield TextareaField::new('shortDescription', 'Description courte')
            ->setRequired(false)
            ->hideOnIndex();
        yield ChoiceField::new('type', 'Type')
            ->setChoices([
                'Fait' => ControversyItemType::Fact,
                'Déclaration' => ControversyItemType::Statement,
                'Publication' => ControversyItemType::Publication,
            ]);
        yield DateField::new('date', 'Date');
        yield BooleanField::new('isVisible', 'Visible');
        yield AssociationField::new('controversy', 'Affaire')
            ->autocomplete()
            ->setRequired(true);
        yield AssociationField::new('people', 'Personnes')->autocomplete();
        yield AssociationField::new('sources', 'Sources')->onlyOnIndex();
    }
}
