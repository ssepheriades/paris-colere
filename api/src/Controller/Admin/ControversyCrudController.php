<?php

namespace App\Controller\Admin;

use App\Entity\Controversy;
use App\Form\ControversyItemType;
use App\Service\UploadedImageField;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends AbstractCrudController<Controversy>
 */
class ControversyCrudController extends AbstractCrudController
{
    public function __construct(private readonly UploadedImageField $images)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Controversy::class;
    }

    public function detail(AdminContext $context): KeyValueStore|Response
    {
        /** @var Controversy $controversy */
        $controversy = $context->getEntity()->getInstance();
        $form = $this->createItemsForm($controversy);

        $responseParameters = parent::detail($context);
        if ($responseParameters instanceof Response) {
            return $responseParameters;
        }

        $responseParameters->set('itemsForm', $form->createView());

        return $responseParameters;
    }

    #[AdminRoute(path: '/{entityId}/items', name: 'items', options: ['methods' => ['POST']])]
    public function saveItems(AdminContext $context): KeyValueStore|Response
    {
        /** @var Controversy $controversy */
        $controversy = $context->getEntity()->getInstance();
        $form = $this->createItemsForm($controversy);
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->container->get('doctrine')->getManager()->flush();

            return $this->redirect($this->container->get(AdminUrlGenerator::class)
                ->unsetAll()
                ->setController(self::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($controversy->getId())
                ->generateUrl());
        }

        $responseParameters = parent::detail($context);
        if ($responseParameters instanceof Response) {
            return $responseParameters;
        }

        $responseParameters->set('itemsForm', $form->createView());

        return $responseParameters;
    }

    private function createItemsForm(Controversy $controversy): FormInterface
    {
        return $this->createFormBuilder($controversy, [
            'action' => $this->container->get(AdminUrlGenerator::class)
                ->unsetAll()
                ->setController(self::class)
                ->setAction('saveItems')
                ->setEntityId($controversy->getId())
                ->generateUrl(),
        ])
            ->add('controversyItems', CollectionType::class, [
                'entry_type' => ControversyItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype_name' => '__item__',
            ])
            ->getForm();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Affaire')
            ->setEntityLabelInPlural('Affaires')
            ->setDefaultSort(['name' => 'ASC'])
            ->setDefaultRowAction(Action::DETAIL)
            ->overrideTemplate('crud/detail', 'admin/controversy/detail.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom');
        yield TextField::new('shortDescription', 'Description courte');
        yield $this->images->illustration();
        yield AssociationField::new('theme', 'Thèmes');
        yield AssociationField::new('controversyItems', 'Faits')->onlyOnIndex();
    }
}
