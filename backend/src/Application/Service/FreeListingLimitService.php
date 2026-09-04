<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Limit\FreeListingLimits;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;

final class FreeListingLimitService
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly CityRepositoryInterface $cityRepository,
    ) {
    }

    public function hasActivePaidLevel(Property $property, ?\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable();

        return $property->getPlacementBaseLevel() > 0
            && !$property->isPlacementIsTrial()
            && $property->getPlacementLevelExpiresAt() !== null
            && $property->getPlacementLevelExpiresAt() > $now;
    }

    public function canPublishFree(Property $property): bool
    {
        if ($this->hasActivePaidLevel($property)) {
            return true;
        }

        $ownerId = $property->getOwnerId();
        $excludeId = $property->getId();

        $accountUsed = $this->propertyRepository->countFreePublishedByOwner($ownerId, $excludeId);
        if ($accountUsed >= FreeListingLimits::MAX_PUBLISHED_PER_ACCOUNT) {
            return false;
        }

        if ($property->getType() !== PropertyType::Apartment->value) {
            return true;
        }

        $city = $this->cityRepository->findById($property->getCityId());
        if ($city === null) {
            return false;
        }

        $cityUsed = $this->propertyRepository->countFreePublishedApartmentsByOwnerInCity(
            $ownerId,
            $property->getCityId(),
            $excludeId,
        );

        return $cityUsed < $city->getFreeApartmentsPerAccount();
    }

    /**
     * @return array{
     *     account: array{used: int, limit: int},
     *     city: array{used: int, limit: int}|null
     * }
     */
    public function describeLimits(Id $ownerId, ?int $cityId, ?string $propertyType): array
    {
        $accountUsed = $this->propertyRepository->countFreePublishedByOwner($ownerId);

        $result = [
            'account' => [
                'used' => $accountUsed,
                'limit' => FreeListingLimits::MAX_PUBLISHED_PER_ACCOUNT,
            ],
            'city' => null,
        ];

        if ($propertyType === PropertyType::Apartment->value && $cityId !== null && $cityId > 0) {
            $city = $this->cityRepository->findById($cityId);
            if ($city !== null) {
                $result['city'] = [
                    'used' => $this->propertyRepository->countFreePublishedApartmentsByOwnerInCity($ownerId, $cityId),
                    'limit' => $city->getFreeApartmentsPerAccount(),
                ];
            }
        }

        return $result;
    }

    public function refreshCityApartmentLimit(int $cityId): void
    {
        $city = $this->cityRepository->findById($cityId);
        if ($city === null) {
            return;
        }

        $counts = $this->propertyRepository->countPublishedByEffectiveLevel(
            PropertyType::Apartment->value,
            $cityId,
        );
        $publishedCount = array_sum($counts);

        $city->setFreeApartmentsPerAccount(
            FreeListingLimits::calculateCityApartmentLimit($publishedCount),
        );
        $this->cityRepository->save($city);
    }

    public function refreshAllCityApartmentLimits(): int
    {
        $countsByCity = $this->propertyRepository->countPublishedApartmentsGroupedByCity();
        $updated = 0;

        foreach ($this->cityRepository->findAll() as $city) {
            $publishedCount = $countsByCity[$city->getId()] ?? 0;
            $newLimit = FreeListingLimits::calculateCityApartmentLimit($publishedCount);
            if ($city->getFreeApartmentsPerAccount() !== $newLimit) {
                $city->setFreeApartmentsPerAccount($newLimit);
                $this->cityRepository->save($city);
                ++$updated;
            }
        }

        return $updated;
    }

    public function maybeRefreshCityLimitAfterStatusChange(Property $property, ?string $previousStatus = null): void
    {
        if ($property->getType() !== PropertyType::Apartment->value) {
            return;
        }

        $currentStatus = $property->getStatus();
        if ($currentStatus === 'published' || $previousStatus === 'published') {
            $this->refreshCityApartmentLimit($property->getCityId());
        }
    }
}
