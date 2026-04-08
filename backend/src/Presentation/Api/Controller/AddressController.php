<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Domain\Property\Repository\RegionRepositoryInterface;
use App\Domain\Property\Repository\RegionDistrictRepositoryInterface;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\StreetRepositoryInterface;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/address', name: 'api_address_')]
class AddressController extends AbstractController
{
    public function __construct(
        private readonly RegionRepositoryInterface $regionRepository,
        private readonly RegionDistrictRepositoryInterface $districtRepository,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly StreetRepositoryInterface $streetRepository,
    ) {
    }

    #[Route('/regions', name: 'regions', methods: ['GET'])]
    public function regions(): JsonResponse
    {
        $regions = $this->regionRepository->findAll();

        return $this->json(ApiResponse::success(
            array_map(fn($r) => [
                'id' => $r->getId(),
                'name' => $r->getName(),
                'slug' => $r->getSlug(),
            ], $regions)
        ));
    }

    #[Route('/regions/{regionId}/districts', name: 'districts', methods: ['GET'])]
    public function districts(int $regionId): JsonResponse
    {
        $districts = $this->districtRepository->findByRegionId($regionId);

        return $this->json(ApiResponse::success(
            array_map(fn($d) => [
                'id' => $d->getId(),
                'name' => $d->getName(),
                'slug' => $d->getSlug(),
                'regionId' => $d->getRegion()->getId(),
            ], $districts)
        ));
    }

    #[Route('/districts/{districtId}/cities', name: 'cities_by_district', methods: ['GET'])]
    public function citiesByDistrict(int $districtId): JsonResponse
    {
        $cities = $this->cityRepository->findByDistrictId($districtId);

        return $this->json(ApiResponse::success(
            array_map(fn($c) => [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'slug' => $c->getSlug(),
                'shortName' => $c->getShortName(),
            ], $cities)
        ));
    }

    #[Route('/cities/search', name: 'cities_search', methods: ['GET'])]
    public function searchCities(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $districtId = $request->query->getInt('districtId') ?: null;

        if (mb_strlen($query) < 2) {
            return $this->json(ApiResponse::success([]));
        }

        $cities = $this->cityRepository->searchByName($query, $districtId);

        return $this->json(ApiResponse::success(
            array_map(fn($c) => [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'slug' => $c->getSlug(),
                'shortName' => $c->getShortName(),
                'districtName' => $c->getRegionDistrict()?->getName(),
                'regionName' => $c->getRegionDistrict()?->getRegion()?->getName(),
                'ruralCouncil' => $c->getRuralCouncil(),
                'isMain' => $c->isMain(),
                'latitude' => $c->getLatitude(),
                'longitude' => $c->getLongitude(),
            ], $cities)
        ));
    }

    #[Route('/cities/{cityId}/streets', name: 'streets', methods: ['GET'])]
    public function streets(int $cityId, Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        $streets = $query
            ? $this->streetRepository->searchByCityId($cityId, $query)
            : $this->streetRepository->findByCityId($cityId);

        return $this->json(ApiResponse::success(
            array_map(fn($s) => [
                'id' => $s->getId(),
                'name' => $s->getName(),
                'slug' => $s->getSlug(),
                'type' => $s->getType(),
            ], $streets)
        ));
    }
}
