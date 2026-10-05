<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Enum\ContactStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Contact>
 */
class ContactCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Contact::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Contact')
            ->setEntityLabelInPlural('Contacts')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield DateTimeField::new('createdAt', 'Reçu le')->hideOnForm();
        yield TextField::new('name', 'Nom');
        yield EmailField::new('email', 'Email');
        yield TextareaField::new('message', 'Message');
        yield AssociationField::new('controversy', 'Affaire')->setRequired(false);
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Nouveau' => ContactStatus::New,
                'En cours' => ContactStatus::Pending,
                'Traité' => ContactStatus::Done,
                'Ignoré' => ContactStatus::Ignored,
            ]);
    }
}
