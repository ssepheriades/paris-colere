<?php

namespace App\Controller\Admin;

use App\Entity\PersonLegalEntity;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @extends AbstractCrudController<PersonLegalEntity>
 */
class PersonLegalEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PersonLegalEntity::class;
    }

    public function index(AdminContext $context): RedirectResponse
    {
        return $this->redirect($this->container->get(AdminUrlGenerator::class)
            ->unsetAll()
            ->setController(PersonCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl());
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Fonction')
            ->setEntityLabelInPlural('Fonctions')
            ->setDefaultSort(['startDate' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('person', 'Personne')->hideOnForm();
        yield AssociationField::new('legalEntity', 'Structure')
            ->autocomplete()
            ->setRequired(true);
        yield TextField::new('position', 'Fonction');
        yield DateField::new('startDate', 'Début')->setRequired(true);
        yield DateField::new('endDate', 'Fin');
    }
}
