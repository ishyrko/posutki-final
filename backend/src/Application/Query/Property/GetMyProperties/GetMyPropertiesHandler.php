<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetMyProperties;

use App\Application\Service\FreeListingLimitService;
use App\Application\DTO\PropertyDTO;
use App\Application\Service\PropertyOwnerPublicContactResolver;
use App\Domain\Favorite\Repository\FavoriteRepositoryInterface;
use App\Domain\Property\Repository\{PropertyRepositoryInterface, CityRepositoryInterface, CityDistrictRepositoryInterface, StreetRepositoryInterface};
use App\Domain\Review\Repository\ReviewRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;

final class GetMyPropertiesHandler
{
    public function __construct(
        private PropertyRepositoryInterface $propertyRepository,
        private CityRepositoryInterface $cityRepository,
        private CityDistrictRepositoryInterface $cityDistrictRepository,
        private StreetRepositoryInterface $streetRepository,
        private FavoriteRepositoryInterface $favoriteRepository,
        private PropertyOwnerPublicContactResolver $ownerPublicContactResolver,
        private ReviewRepositoryInterface $reviewRepository,
        private FreeListingLimitService $freeListingLimitService,
    ) {
    }

    public function __invoke(GetMyPropertiesQuery $query): array
    {
        $properties = $this->propertyRepository->findByOwner(
            $query->userId,
            $query->page,
            $query->limit
        );

        $ownerId = Id::fromString($query->userId);
        $unviewedByProperty = $this->reviewRepository->countUnviewedGroupedByPropertyForOwner($ownerId);

        $cityIds = array_unique(array_map(
            fn($p) => $p->getCityId(),
            $properties
        ));

        $streetIds = array_filter(array_unique(array_map(
            fn($p) => $p->getStreetId(),
            $properties
        )));

        $cityDistrictIds = array_filter(array_unique(array_map(
            fn($p) => $p->getCityDistrictId(),
            $properties
        )));

        $cities = [];
        foreach ($cityIds as $cityId) {
            $city = $this->cityRepository->findById($cityId);
            if ($city !== null) {
                $cities[$cityId] = $city;
            }
        }

        $streets = [];
        foreach ($streetIds as $streetId) {
            $street = $this->streetRepository->findById($streetId);
            if ($street !== null) {
                $streets[$streetId] = $street;
            }
        }

        $cityDistricts = [];
        foreach ($cityDistrictIds as $cityDistrictId) {
            $cityDistrict = $this->cityDistrictRepository->findById($cityDistrictId);
            if ($cityDistrict !== null) {
                $cityDistricts[$cityDistrictId] = $cityDistrict;
            }
        }

        $ownerIds = array_values(array_unique(array_map(
            static fn($property) => $property->getOwnerId()->getValue(),
            $properties
        )));
        $ownerContacts = $this->ownerPublicContactResolver->resolveForOwnerIds($ownerIds);

        return array_map(
            function ($property) use ($cities, $streets, $cityDistricts, $ownerContacts, $unviewedByProperty) {
                $ownerId = $property->getOwnerId()->getValue();
                $contact = $ownerContacts[$ownerId] ?? ['phone' => null, 'name' => null, 'phones' => [], 'telegram' => null];
                $propertyId = $property->getId()->getValue();
                $canPublishFree = null;
                $freeLimitBlockIntro = null;
                if ($property->getStatus() === 'awaiting_payment') {
                    $canPublishFree = $this->freeListingLimitService->canPublishFree($property);
                    if (!$canPublishFree) {
                        $freeLimitBlockIntro = $this->freeListingLimitService->buildLimitExceededIntro($property);
                    }
                }

                return PropertyDTO::fromEntity(
                    $property,
                    $cities[$property->getCityId()] ?? null,
                    $streets[$property->getStreetId()] ?? null,
                    $cityDistricts[$property->getCityDistrictId()] ?? null,
                    null,
                    null,
                    [],
                    $this->favoriteRepository->countByProperty($property->getId()),
                    null,
                    $contact,
                    includeAllImages: true,
                    unviewedReviewsCount: $unviewedByProperty[$propertyId] ?? 0,
                    canPublishFree: $canPublishFree,
                    freeLimitBlockIntro: $freeLimitBlockIntro,
                );
            },
            $properties
        );
    }
}
