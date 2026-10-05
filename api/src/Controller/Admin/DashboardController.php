<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(ControversyCrudController::class)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Paris Colère')
            ->setLocales(['fr']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkTo(ControversyCrudController::class, 'Affaires', 'fa fa-folder');
        yield MenuItem::linkTo(ThemeCrudController::class, 'Thèmes', 'fa fa-tags');
        yield MenuItem::linkTo(PersonCrudController::class, 'Personnes', 'fa fa-user');
        yield MenuItem::linkTo(LegalEntityCrudController::class, 'Structures', 'fa fa-building');
        yield MenuItem::linkTo(PartyCrudController::class, 'Partis', 'fa fa-flag');
        yield MenuItem::linkTo(ContactCrudController::class, 'Contacts', 'fa fa-envelope');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-lock');
    }
}
