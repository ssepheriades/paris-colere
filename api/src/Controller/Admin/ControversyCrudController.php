<?php

namespace App\Controller\Admin;

use App\Entity\Controversy;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Controversy>
 */
class ControversyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Controversy::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Affaire')
            ->setEntityLabelInPlural('Affaires')
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom');
        yield TextField::new('shortDescription', 'Description courte');
        yield AssociationField::new('controversyItems', 'Faits')->onlyOnIndex();
    }
}
