<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Domain\Property\Entity\Landmark;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\LandmarkRepositoryInterface;
use App\Infrastructure\Service\NearestMetroStationResolver;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/cities', name: 'api_cities_landmarks_')]
final class LandmarkController extends AbstractController
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
        private readonly LandmarkRepositoryInterface $landmarkRepository,
        private readonly NearestMetroStationResolver $nearestMetroStationResolver,
    ) {
    }

    #[Route('/{citySlug}/landmarks', name: 'list', methods: ['GET'])]
    public function listByCitySlug(string $citySlug): JsonResponse
    {
        $city = $this->cityRepository->findBySlug($citySlug);
        if ($city === null) {
            return $this->json(
                ApiResponse::error('Город не найден', 404),
                404
            );
        }

        $landmarks = $this->landmarkRepository->findActiveByCityId($city->getId());

        return $this->json(ApiResponse::success(array_map(
            static fn (Landmark $landmark) => [
                'id' => $landmark->getId(),
                'name' => $landmark->getName(),
                'slug' => $landmark->getSlug(),
                'category' => $landmark->getCategory(),
                'imageUrl' => self::normalizeLandmarkImageUrl($landmark->getImageUrl()),
            ],
            $landmarks,
        )));
    }

    #[Route('/{citySlug}/landmarks/{slug}', name: 'get', methods: ['GET'])]
    public function getByCitySlugAndSlug(string $citySlug, string $slug): JsonResponse
    {
        $city = $this->cityRepository->findBySlug($citySlug);
        if ($city === null) {
            return $this->json(
                ApiResponse::error('Город не найден', 404),
                404
            );
        }

        $landmark = $this->landmarkRepository->findByCityIdAndSlug($city->getId(), $slug);
        if ($landmark === null) {
            return $this->json(
                ApiResponse::error('Достопримечательность не найдена', 404),
                404
            );
        }

        $nearestMetro = null;
        if ($landmark->hasCoordinates()) {
            $nearestMetro = $this->nearestMetroStationResolver->resolve(
                $landmark->getCityId(),
                $landmark->getLatitude(),
                $landmark->getLongitude(),
            );
        }

        return $this->json(ApiResponse::success([
            'id' => $landmark->getId(),
            'name' => $landmark->getName(),
            'nameGenitive' => $landmark->getNameGenitive(),
            'slug' => $landmark->getSlug(),
            'category' => $landmark->getCategory(),
            'shortDescription' => $landmark->getShortDescription(),
            'description' => $landmark->getDescription(),
            'imageUrl' => self::normalizeLandmarkImageUrl($landmark->getImageUrl()),
            'address' => $landmark->getAddress(),
            'facts' => $landmark->getFacts(),
            'guestTips' => $landmark->getGuestTips(),
            'nearestMetro' => $nearestMetro,
            'latitude' => $landmark->getLatitude(),
            'longitude' => $landmark->getLongitude(),
        ]));
    }

    private static function normalizeLandmarkImageUrl(?string $imageUrl): ?string
    {
        if ($imageUrl === null || trim($imageUrl) === '') {
            return null;
        }

        if (str_contains($imageUrl, '://') || str_starts_with($imageUrl, '//')) {
            return $imageUrl;
        }

        if (str_starts_with($imageUrl, '/uploads/')) {
            return $imageUrl;
        }

        if (!str_starts_with($imageUrl, '/')) {
            $cleaned = preg_replace('#^(?:uploads/)?landmarks/#', '', $imageUrl) ?? $imageUrl;

            return '/uploads/landmarks/' . $cleaned;
        }

        return $imageUrl;
    }
}
