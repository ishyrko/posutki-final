<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetProperty;

use App\Application\Service\PropertyCalendarAggregator;
use App\Application\Service\PropertyOwnerPublicContactResolver;
use App\Application\DTO\PropertyDTO;
use App\Domain\Property\Enum\DealType;
use App\Domain\Property\Enum\SellerType;
use App\Domain\Property\Repository\{
    PropertyRepositoryInterface,
    CityRepositoryInterface,
    CityDistrictRepositoryInterface,
    CityMicrodistrictRepositoryInterface,
    ResidentialComplexRepositoryInterface,
    StreetRepositoryInterface,
    MetroStationRepositoryInterface,
    PropertyMetroStationRepositoryInterface,
    PropertyLandmarkRepositoryInterface,
    LandmarkRepositoryInterface,
};
use App\Domain\Review\Repository\ReviewRepositoryInterface;
use App\Domain\User\Repository\UserBusinessProfileRepositoryInterface;
use App\Domain\User\Repository\UserIndividualProfileRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\Exception\NotFoundException;

final class GetPropertyHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly CityDistrictRepositoryInterface $cityDistrictRepository,
        private readonly CityMicrodistrictRepositoryInterface $cityMicrodistrictRepository,
        private readonly ResidentialComplexRepositoryInterface $residentialComplexRepository,
        private readonly StreetRepositoryInterface $streetRepository,
        private readonly MetroStationRepositoryInterface $metroStationRepository,
        private readonly PropertyMetroStationRepositoryInterface $propertyMetroStationRepository,
        private readonly PropertyLandmarkRepositoryInterface $propertyLandmarkRepository,
        private readonly LandmarkRepositoryInterface $landmarkRepository,
        private readonly UserIndividualProfileRepositoryInterface $userIndividualProfileRepository,
        private readonly UserBusinessProfileRepositoryInterface $userBusinessProfileRepository,
        private readonly PropertyOwnerPublicContactResolver $ownerPublicContactResolver,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly PropertyCalendarAggregator $propertyCalendarAggregator,
    ) {
    }

    public function __invoke(GetPropertyQuery $query): PropertyDTO
    {
        $propertyId = Id::fromString($query->propertyId);
        $property = $this->propertyRepository->findById($propertyId);

        if ($property === null) {
            throw new NotFoundException('Объявление не найдено');
        }

        $isOwner = $query->viewerUserId !== null && $property->isOwnedBy($query->viewerUserId);

        if (in_array($property->getStatus(), ['archived', 'deleted'], true) && !$isOwner) {
            throw new NotFoundException('Объявление не найдено');
        }

        if ($property->getStatus() !== 'published' && !$isOwner) {
            throw new NotFoundException('Объявление не найдено');
        }

        $city = $this->cityRepository->findById($property->getCityId());

        if ($city === null) {
            throw new DomainException('Город не найден');
        }

        $street = null;
        if ($property->getStreetId() !== null) {
            $street = $this->streetRepository->findById($property->getStreetId());
        }

        $cityDistrict = null;
        if ($property->getCityDistrictId() !== null) {
            $cityDistrict = $this->cityDistrictRepository->findById($property->getCityDistrictId());
        }

        $cityMicrodistrict = null;
        if ($property->getCityMicrodistrictId() !== null) {
            $cityMicrodistrict = $this->cityMicrodistrictRepository->findById($property->getCityMicrodistrictId());
        }

        $residentialComplex = null;
        if ($property->getResidentialComplexId() !== null) {
            $residentialComplex = $this->residentialComplexRepository->findById($property->getResidentialComplexId());
        }

        $propertyMetroStations = $this->propertyMetroStationRepository->findByPropertyIds([$property->getId()->getValue()]);
        $metroStationIds = array_values(array_unique(array_map(
            static fn($propertyMetroStation) => $propertyMetroStation->getMetroStationId(),
            $propertyMetroStations
        )));

        $metroStationsById = [];
        foreach ($this->metroStationRepository->findByIds($metroStationIds) as $metroStation) {
            $metroStationsById[$metroStation->getId()] = $metroStation;
        }

        $nearbyMetroStations = [];
        foreach ($propertyMetroStations as $propertyMetroStation) {
            $metroStation = $metroStationsById[$propertyMetroStation->getMetroStationId()] ?? null;
            if ($metroStation === null) {
                continue;
            }

            $nearbyMetroStations[] = [
                'id' => $metroStation->getId(),
                'name' => $metroStation->getName(),
                'slug' => $metroStation->getSlug(),
                'line' => $metroStation->getLine(),
                'distanceKm' => $propertyMetroStation->getDistanceKm(),
            ];
        }

        usort(
            $nearbyMetroStations,
            static fn(array $a, array $b): int => $a['distanceKm'] <=> $b['distanceKm']
        );

        $nearbyLandmarks = [];
        if ($property->getType() === 'apartment') {
            $propertyLandmarks = $this->propertyLandmarkRepository->findByPropertyId($property->getId()->getValue());
            $landmarkIds = array_values(array_unique(array_map(
                static fn($propertyLandmark) => $propertyLandmark->getLandmarkId(),
                $propertyLandmarks,
            )));

            $landmarksById = [];
            foreach ($this->landmarkRepository->findActiveByIds($landmarkIds) as $landmark) {
                $landmarksById[$landmark->getId()] = $landmark;
            }

            foreach ($propertyLandmarks as $propertyLandmark) {
                $landmark = $landmarksById[$propertyLandmark->getLandmarkId()] ?? null;
                if ($landmark === null) {
                    continue;
                }

                $nearbyLandmarks[] = [
                    'id' => $landmark->getId(),
                    'name' => $landmark->getName(),
                    'slug' => $landmark->getSlug(),
                    'category' => $landmark->getCategory(),
                    'imageUrl' => self::normalizeLandmarkImageUrl($landmark->getImageUrl()),
                    'distanceKm' => $propertyLandmark->getDistanceKm(),
                ];
            }
        }

        $dailySellerLegalProfile = null;
        if ($property->getDealType() === DealType::Daily->value) {
            $sellerType = $property->getSellerType();
            $ownerId = $property->getOwnerId();

            if ($sellerType === SellerType::Individual->value) {
                $profile = $this->userIndividualProfileRepository->findByUserId($ownerId);
                if ($profile !== null) {
                    $dailySellerLegalProfile = [
                        'type' => SellerType::Individual->value,
                        'lastName' => $profile->getLastName(),
                        'firstName' => $profile->getFirstName(),
                        'middleName' => $profile->getMiddleName(),
                        'unp' => $profile->getUnp(),
                    ];
                }
            } elseif ($sellerType === SellerType::Business->value) {
                $profile = $this->userBusinessProfileRepository->findByUserId($ownerId);
                if ($profile !== null) {
                    $dailySellerLegalProfile = [
                        'type' => SellerType::Business->value,
                        'organizationName' => $profile->getOrganizationName(),
                        'contactName' => $profile->getContactName(),
                        'unp' => $profile->getUnp(),
                    ];
                }
            }
        }

        $ownerId = $property->getOwnerId()->getValue();
        $ownerContact = $this->ownerPublicContactResolver->resolveForOwnerIds([$ownerId])[$ownerId]
            ?? ['phone' => null, 'name' => null, 'phones' => [], 'telegram' => null];

        $reviewAggregate = $this->reviewRepository->getAggregateByPropertyId($property->getId());

        $viewerReview = null;
        if ($query->viewerUserId !== null) {
            try {
                $viewerId = Id::fromString($query->viewerUserId);
                $existingReview = $this->reviewRepository->findByAuthorAndProperty($viewerId, $property->getId());
                if ($existingReview !== null && $existingReview->getId() !== null) {
                    $viewerReview = [
                        'id' => $existingReview->getId()->getValue(),
                        'status' => $existingReview->getStatus()->value,
                    ];
                }
            } catch (\InvalidArgumentException) {
                // ignore invalid viewer id
            }
        }

        return PropertyDTO::fromEntity(
            $property,
            $city,
            $street,
            $cityDistrict,
            $cityMicrodistrict,
            $residentialComplex,
            $nearbyMetroStations,
            0,
            $dailySellerLegalProfile,
            $ownerContact,
            $reviewAggregate['avg'],
            $reviewAggregate['count'],
            $viewerReview,
            $this->propertyCalendarAggregator->getCalendarLastUpdatedAt($property),
            includeAllImages: $isOwner,
            nearbyLandmarks: $nearbyLandmarks,
        );
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
