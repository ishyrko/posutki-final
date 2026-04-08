<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Domain\Property\Repository\MetroStationRepositoryInterface;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/metro', name: 'api_metro_')]
final class MetroController extends AbstractController
{
    public function __construct(
        private readonly MetroStationRepositoryInterface $metroStationRepository,
    ) {
    }

    #[Route('/stations', name: 'stations', methods: ['GET'])]
    public function stations(Request $request): JsonResponse
    {
        $cityId = $request->query->getInt('cityId', 1);
        $stations = $this->metroStationRepository->findByCityId($cityId);

        return $this->json(ApiResponse::success(
            array_map(static fn($station) => [
                'id' => $station->getId(),
                'cityId' => $station->getCityId(),
                'name' => $station->getName(),
                'slug' => $station->getSlug(),
                'line' => $station->getLine(),
                'order' => $station->getSortOrder(),
                'latitude' => $station->getLatitude(),
                'longitude' => $station->getLongitude(),
            ], $stations)
        ));
    }
}
