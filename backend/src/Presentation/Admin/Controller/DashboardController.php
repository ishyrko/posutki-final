<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Query\Property\GetAdminPropertyStatsOverview\GetAdminPropertyStatsOverviewHandler;
use App\Application\Query\Property\GetAdminPropertyStatsOverview\GetAdminPropertyStatsOverviewQuery;
use App\Domain\Article\Entity\Article;
use App\Domain\Article\Entity\ArticleCategory;
use App\Domain\StaticPage\Entity\StaticPage;
use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\CityDistrict;
use App\Domain\Property\Entity\CityMicrodistrict;
use App\Domain\Property\Entity\Landmark;
use App\Domain\Property\Entity\MetroStation;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyPlacementLevelPrice;
use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Entity\PropertyPlacementScopeSettings;
use App\Domain\Property\Entity\ResidentialComplex;
use App\Domain\Review\Entity\Review;
use App\Domain\Property\Entity\Street;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\RegionRepositoryInterface;
use App\Domain\User\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly GetAdminPropertyStatsOverviewHandler $adminPropertyStatsOverviewHandler,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly RegionRepositoryInterface $regionRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function index(): Response
    {
        $request = $this->requestStack->getCurrentRequest();
        $periodParam = $request?->query->get('period');
        $period = is_numeric($periodParam) ? (int) $periodParam : 30;
        $dateFrom = null;
        $dateTo = null;
        if ($periodParam === 'custom') {
            $dateFrom = $request?->query->getString('dateFrom') ?: null;
            $dateTo = $request?->query->getString('dateTo') ?: null;
        }
        $type = $request?->query->getString('type') ?? '';
        $cityIdRaw = $request?->query->get('cityId');
        $cityId = is_numeric($cityIdRaw) ? (int) $cityIdRaw : null;
        $regionIdRaw = $request?->query->get('regionId');
        $regionId = is_numeric($regionIdRaw) ? (int) $regionIdRaw : null;

        if ($type === PropertyType::Apartment->value) {
            $regionId = null;
        } elseif ($type === PropertyType::House->value) {
            $cityId = null;
        } else {
            $cityId = null;
            $regionId = null;
        }

        $stats = ($this->adminPropertyStatsOverviewHandler)(new GetAdminPropertyStatsOverviewQuery(
            period: $period,
            propertyType: $type !== '' ? $type : null,
            cityId: $cityId,
            regionId: $regionId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        ));

        $selectedCityName = null;
        if ($cityId !== null) {
            $selectedCity = $this->cityRepository->findById($cityId);
            $selectedCityName = $selectedCity?->getName();
        }

        $selectedRegionName = null;
        if ($regionId !== null) {
            $selectedRegion = $this->regionRepository->findById($regionId);
            $selectedRegionName = $selectedRegion?->getName();
        }

        return $this->render('admin/stats_dashboard.html.twig', [
            'stats' => $stats,
            'apartmentCities' => $this->findCitiesWithApartmentListings(),
            'houseRegions' => $this->regionRepository->findAll(),
            'selectedCityName' => $selectedCityName,
            'selectedRegionName' => $selectedRegionName,
            'filters' => [
                'period' => $stats['dateFrom'] !== null ? 'custom' : (string) $stats['period'],
                'dateFrom' => $stats['dateFrom'] ?? '',
                'dateTo' => $stats['dateTo'] ?? '',
                'type' => $stats['propertyType'] ?? '',
                'cityId' => $stats['cityId'] ?? '',
                'regionId' => $stats['regionId'] ?? '',
            ],
        ]);
    }

    /**
     * @return City[]
     */
    private function findCitiesWithApartmentListings(): array
    {
        $cities = [];
        foreach ($this->propertyRepository->findCityIdsWithListings(PropertyType::Apartment->value) as $cityId) {
            $city = $this->cityRepository->findById($cityId);
            if ($city !== null) {
                $cities[] = $city;
            }
        }

        usort(
            $cities,
            static fn(City $a, City $b): int => strnatcasecmp($a->getName(), $b->getName()),
        );

        return $cities;
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
            'page_title' => 'Posutki.by — Админ',
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
            ->setTitle('Posutki.by — Админ')
            ->setFaviconPath('favicon.ico')
            ->setLocales(['ru']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Статистика', 'fa fa-chart-line');

        yield MenuItem::section('Контент');
        yield MenuItem::linkToCrud('Объявления', 'fa fa-building', Property::class)
            ->setController(PropertyCrudController::class);
        yield MenuItem::linkToCrud('Модерация объявлений', 'fa fa-shield', Property::class)
            ->setController(PropertyModerationCrudController::class);
        yield MenuItem::linkToCrud('Модерация отзывов', 'fa fa-star', Review::class)
            ->setController(ReviewCrudController::class);
        yield MenuItem::linkToCrud('Статьи', 'fa fa-newspaper', Article::class);
        yield MenuItem::linkToCrud('Категории статей', 'fa fa-folder', ArticleCategory::class);
        yield MenuItem::linkToCrud('Страницы', 'fa fa-file-alt', StaticPage::class);

        yield MenuItem::section('Размещение');
        yield MenuItem::linkToCrud('VIP-тарифы — квартиры', 'fa fa-building', PropertyPlacementLevelPrice::class)
            ->setController(ApartmentPlacementLevelPriceCrudController::class);
        yield MenuItem::linkToCrud('VIP-тарифы — дома', 'fa fa-house', PropertyPlacementLevelPrice::class)
            ->setController(HousePlacementLevelPriceCrudController::class);
        yield MenuItem::linkToCrud('Настройки VIP — квартиры', 'fa fa-building', PropertyPlacementScopeSettings::class)
            ->setController(ApartmentPlacementScopeSettingsCrudController::class);
        yield MenuItem::linkToCrud('Настройки VIP — дома', 'fa fa-house', PropertyPlacementScopeSettings::class)
            ->setController(HousePlacementScopeSettingsCrudController::class);
        yield MenuItem::linkToCrud('Заявки на размещение', 'fa fa-receipt', PropertyPlacementPurchase::class);

        yield MenuItem::section('Справочники');
        yield MenuItem::linkToCrud('Города', 'fa fa-city', City::class);
        yield MenuItem::linkToCrud('Районы города', 'fa fa-map', CityDistrict::class);
        yield MenuItem::linkToCrud('Микрорайоны', 'fa fa-map-location-dot', CityMicrodistrict::class);
        yield MenuItem::linkToCrud('Жилые комплексы', 'fa fa-building', ResidentialComplex::class);
        yield MenuItem::linkToCrud('Улицы', 'fa fa-road', Street::class);
        yield MenuItem::linkToCrud('Метро', 'fa fa-train-subway', MetroStation::class);
        yield MenuItem::linkToCrud('Достопримечательности', 'fa fa-landmark', Landmark::class);

        yield MenuItem::section('Пользователи');
        yield MenuItem::linkToCrud('Пользователи', 'fa fa-users', User::class);
    }
}
