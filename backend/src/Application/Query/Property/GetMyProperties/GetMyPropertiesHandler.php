<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetMyProperties;

use App\Application\DTO\PropertyDTO;
use App\Application\Service\FreeListingLimitService;
use App\Application\Service\PropertyOwnerPublicContactResolver;
use App\Domain\Favorite\Repository\FavoriteRepositoryInterface;
use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\CityDistrict;
use App\Domain\Property\Entity\Street;
use App\Domain\Property\Repository\CityDistrictRepositoryInterface;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\StreetRepositoryInterface;
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
        $filters = [
            'status' => $query->status,
            'q' => $query->q,
            'cityId' => $query->cityId,
            'sort' => $query->sort,
            'sortOrder' => $query->sortOrder,
        ];

        $properties = $this->propertyRepository->findByOwner(
            $query->userId,
            $query->page,
            $query->limit,
            $filters,
        );
        $total = $this->propertyRepository->countByOwner($query->userId, $filters);
        $statusCounts = $this->propertyRepository->countByOwnerGroupedByStatus($query->userId, $filters);

        $ownerId = Id::fromString($query->userId);
        $unviewedByProperty = $this->reviewRepository->countUnviewedGroupedByPropertyForOwner($ownerId);

        $cityIds = array_values(array_unique(array_filter(array_map(
            static fn($p) => $p->getCityId(),
            $properties,
        ))));
        $streetIds = array_values(array_unique(array_filter(array_map(
            static fn($p) => $p->getStreetId(),
            $properties,
        ))));
        $cityDistrictIds = array_values(array_unique(array_filter(array_map(
            static fn($p) => $p->getCityDistrictId(),
            $properties,
        ))));

        $cities = $this->indexById($this->cityRepository->findByIds($cityIds));
        $streets = $this->indexById($this->streetRepository->findByIds($streetIds));
        $cityDistricts = $this->indexById($this->cityDistrictRepository->findByIds($cityDistrictIds));

        $ownerIds = array_values(array_unique(array_map(
            static fn($property) => $property->getOwnerId()->getValue(),
            $properties,
        )));
        $ownerContacts = $this->ownerPublicContactResolver->resolveForOwnerIds($ownerIds);

        $items = array_map(
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
            $properties,
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $query->page,
            'limit' => $query->limit,
            'counts' => [
                'published' => $statusCounts['published'] ?? 0,
                'moderation' => $statusCounts['moderation'] ?? 0,
                'awaiting_payment' => $statusCounts['awaiting_payment'] ?? 0,
                'rejected' => $statusCounts['rejected'] ?? 0,
                'inactive' => $statusCounts['archived'] ?? 0,
                'draft' => $statusCounts['draft'] ?? 0,
                'all' => array_sum($statusCounts),
            ],
        ];
    }

    /**
     * @param list<City|Street|CityDistrict> $entities
     *
     * @return array<int, City|Street|CityDistrict>
     */
    private function indexById(array $entities): array
    {
        $indexed = [];
        foreach ($entities as $entity) {
            $indexed[$entity->getId()] = $entity;
        }

        return $indexed;
    }
}
