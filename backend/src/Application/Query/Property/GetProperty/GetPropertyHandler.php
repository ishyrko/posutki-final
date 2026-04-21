<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetProperty;

use App\Application\DTO\PropertyDTO;
use App\Domain\Property\Enum\DealType;
use App\Domain\Property\Enum\SellerType;
use App\Domain\Property\Repository\{
    PropertyRepositoryInterface,
    CityRepositoryInterface,
    StreetRepositoryInterface,
    MetroStationRepositoryInterface,
    PropertyMetroStationRepositoryInterface,
    PropertyDailyStatRepositoryInterface
};
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
        private readonly StreetRepositoryInterface $streetRepository,
        private readonly MetroStationRepositoryInterface $metroStationRepository,
        private readonly PropertyMetroStationRepositoryInterface $propertyMetroStationRepository,
        private readonly PropertyDailyStatRepositoryInterface $propertyDailyStatRepository,
        private readonly UserIndividualProfileRepositoryInterface $userIndividualProfileRepository,
        private readonly UserBusinessProfileRepositoryInterface $userBusinessProfileRepository,
    ) {
    }

    public function __invoke(GetPropertyQuery $query): PropertyDTO
    {
        $propertyId = Id::fromString($query->propertyId);
        $property = $this->propertyRepository->findById($propertyId);

        if ($property === null) {
            throw new NotFoundException('Объявление не найдено');
        }

        if ($property->getStatus() !== 'published') {
            if ($query->viewerUserId === null || !$property->isOwnedBy($query->viewerUserId)) {
                throw new NotFoundException('Объявление не найдено');
            }
        }

        $city = $this->cityRepository->findById($property->getCityId());

        if ($city === null) {
            throw new DomainException('Город не найден');
        }

        $street = null;
        if ($property->getStreetId() !== null) {
            $street = $this->streetRepository->findById($property->getStreetId());
        }

        $property->incrementViews();
        $this->propertyRepository->save($property);
        $this->propertyDailyStatRepository->upsertView($property->getId()->getValue(), new \DateTimeImmutable());

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

        return PropertyDTO::fromEntity($property, $city, $street, $nearbyMetroStations, 0, $dailySellerLegalProfile);
    }
}
