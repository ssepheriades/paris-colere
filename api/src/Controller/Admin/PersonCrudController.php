<?php

namespace App\Controller\Admin;

use App\Entity\Person;
use App\Form\PersonLegalEntityType;
use App\Service\UploadedImageField;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends AbstractCrudController<Person>
 */
class PersonCrudController extends AbstractCrudController
{
    public function __construct(private readonly UploadedImageField $images)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Person::class;
    }

    public function detail(AdminContext $context): KeyValueStore|Response
    {
        /** @var Person $person */
        $person = $context->getEntity()->getInstance();
        $form = $this->createFunctionsForm($person);

        $responseParameters = parent::detail($context);
        if ($responseParameters instanceof Response) {
            return $responseParameters;
        }

        $responseParameters->set('functionsForm', $form->createView());

        return $responseParameters;
    }

    #[AdminRoute(path: '/{entityId}/functions', name: 'functions', options: ['methods' => ['POST']])]
    public function saveFunctions(AdminContext $context): KeyValueStore|Response
    {
        /** @var Person $person */
        $person = $context->getEntity()->getInstance();
        $form = $this->createFunctionsForm($person);
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->container->get('doctrine')->getManager()->flush();

            return $this->redirect($this->container->get(AdminUrlGenerator::class)
                ->unsetAll()
                ->setController(self::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($person->getId())
                ->generateUrl());
        }

        $responseParameters = parent::detail($context);
        if ($responseParameters instanceof Response) {
            return $responseParameters;
        }

        $responseParameters->set('functionsForm', $form->createView());

        return $responseParameters;
    }

    private function createFunctionsForm(Person $person): FormInterface
    {
        return $this->createFormBuilder($person, [
            'action' => $this->container->get(AdminUrlGenerator::class)
                ->unsetAll()
                ->setController(self::class)
                ->setAction('saveFunctions')
                ->setEntityId($person->getId())
                ->generateUrl(),
        ])
            ->add('personLegalEntities', CollectionType::class, [
                'entry_type' => PersonLegalEntityType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype_name' => '__name__',
            ])
            ->getForm();
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Personne')
            ->setEntityLabelInPlural('Personnes')
            ->setDefaultSort(['lastname' => 'ASC'])
            ->setDefaultRowAction(Action::DETAIL)
            ->overrideTemplate('crud/detail', 'admin/person/detail.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('firstname', 'Prénom');
        yield TextField::new('lastname', 'Nom');
        yield $this->images->photo();
        yield AssociationField::new('party', 'Partis')->autocomplete();
        yield AssociationField::new('personLegalEntities', 'Fonctions')->onlyOnIndex();
        yield CollectionField::new('personLegalEntities', 'Fonctions')
            ->useEntryCrudForm(PersonLegalEntityCrudController::class)
            ->setEntryIsComplex()
            ->setColumns(12)
            ->hideOnIndex()
            ->hideOnDetail();
        yield AssociationField::new('controversyItems', 'Faits')->onlyOnIndex();
    }
}
