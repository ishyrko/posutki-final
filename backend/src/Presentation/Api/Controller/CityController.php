<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/cities', name: 'api_cities_')]
class CityController extends AbstractController
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
    ) {
    }

    #[Route('/{slug}', name: 'get_by_slug', methods: ['GET'])]
    public function getBySlug(string $slug): JsonResponse
    {
        $city = $this->cityRepository->findBySlug($slug);

        if ($city === null) {
            return $this->json(
                ApiResponse::error('Город не найден', 404),
                404
            );
        }

        $district = $city->getRegionDistrict();
        $region = $district?->getRegion();

        return $this->json(ApiResponse::success([
            'id' => $city->getId(),
            'name' => $city->getName(),
            'slug' => $city->getSlug(),
            'shortName' => $city->getShortName(),
            'latitude' => $city->getLatitude(),
            'longitude' => $city->getLongitude(),
            'isMain' => $city->isMain(),
            'district' => $district ? [
                'id' => $district->getId(),
                'name' => $district->getName(),
            ] : null,
            'region' => $region ? [
                'id' => $region->getId(),
                'name' => $region->getName(),
            ] : null,
        ]));
    }
}
