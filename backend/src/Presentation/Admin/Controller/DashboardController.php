<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Article\Entity\Article;
use App\Domain\Article\Entity\ArticleCategory;
use App\Domain\StaticPage\Entity\StaticPage;
use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\MetroStation;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\Street;
use App\Domain\User\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator->setController(PropertyCrudController::class)->generateUrl());
    }

    #[Route('/admin/login', name: 'admin_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@EasyAdmin/page/login.html.twig', [
            'error' => $error,
            'last_username' => $lastUsername,
            'page_title' => 'RNB.by — Админ',
            'csrf_token_intention' => 'authenticate',
            'username_label' => 'Email',
            'password_label' => 'Пароль',
            'sign_in_label' => 'Войти',
            'username_parameter' => '_username',
            'password_parameter' => '_password',
        ]);
    }

    #[Route('/admin/logout', name: 'admin_logout')]
    public function logout(): void
    {
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('RNB.by — Админ')
            ->setFaviconPath('favicon.ico')
            ->setLocales(['ru']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Главная', 'fa fa-home');

        yield MenuItem::section('Контент');
        yield MenuItem::linkToCrud('Объявления', 'fa fa-building', Property::class);
        yield MenuItem::linkToCrud('Модерация объявлений', 'fa fa-shield', Property::class)
            ->setController(PropertyModerationCrudController::class);
        yield MenuItem::linkToCrud('Статьи', 'fa fa-newspaper', Article::class);
        yield MenuItem::linkToCrud('Категории статей', 'fa fa-folder', ArticleCategory::class);
        yield MenuItem::linkToCrud('Страницы', 'fa fa-file-alt', StaticPage::class);

        yield MenuItem::section('Справочники');
        yield MenuItem::linkToCrud('Города', 'fa fa-city', City::class);
        yield MenuItem::linkToCrud('Улицы', 'fa fa-road', Street::class);
        yield MenuItem::linkToCrud('Метро', 'fa fa-train-subway', MetroStation::class);

        yield MenuItem::section('Пользователи');
        yield MenuItem::linkToCrud('Пользователи', 'fa fa-users', User::class);
    }
}
