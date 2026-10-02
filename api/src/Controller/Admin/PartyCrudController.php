<?php

namespace App\Controller\Admin;

use App\Entity\Party;
use App\Service\UploadedImageField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Party>
 */
class PartyCrudController extends AbstractCrudController
{
    public function __construct(private readonly UploadedImageField $images)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Party::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Parti')
            ->setEntityLabelInPlural('Partis')
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom');
        yield ColorField::new('color', 'Couleur');
        yield $this->images->logo();
        yield AssociationField::new('people', 'Personnes')->onlyOnIndex();
    }
}
