<?php

declare(strict_types=1);

namespace App\Application\Command\Property\UpdateProperty;

use App\Domain\Property\Entity\PropertyRevision;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\PropertyRevisionRepositoryInterface;
use App\Domain\Property\Validation\DailyRentDetailsValidator;
use App\Domain\Property\Validation\DealConditionsValidator;
use App\Domain\Property\Validation\FloorTotalFloorsValidator;
use App\Domain\Property\Validation\PropertyDealCombinationValidator;
use App\Domain\Property\Validation\RoomDealDetailsValidator;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\Exception\UnauthorizedException;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Service\ExchangeRateService;
use App\Infrastructure\Service\MetroProximityCalculator;

readonly class UpdatePropertyHandler
{
    public function __construct(
        private PropertyRepositoryInterface $propertyRepository,
        private PropertyRevisionRepositoryInterface $revisionRepository,
        private ExchangeRateService $exchangeRateService,
        private MetroProximityCalculator $metroProximityCalculator,
    ) {
    }

    public function __invoke(UpdatePropertyCommand $command): void
    {
        $property = $this->propertyRepository->findById(Id::fromString($command->propertyId));

        if (!$property) {
            throw new \InvalidArgumentException('Объявление не найдено');
        }

        if (!$property->isOwnedBy($command->userId)) {
            throw new UnauthorizedException('Нет прав на изменение этого объявления');
        }

        $effectiveFloor = $command->floor ?? $property->getFloor();
        $effectiveTotalFloors = $command->totalFloors ?? $property->getTotalFloors();
        FloorTotalFloorsValidator::assertValid($effectiveFloor, $effectiveTotalFloors);

        $effectiveDealType = $command->dealType ?? $property->getDealType();
        $effectiveType = $command->type ?? $property->getType();
        if ($command->dealConditions !== null) {
            DealConditionsValidator::assertValid($command->dealConditions, $effectiveDealType, $effectiveType);
        }
        $effectiveLandArea = $command->landArea ?? $property->getLandArea();
        DailyRentDetailsValidator::assertValid(
            dealType: $effectiveDealType,
            propertyType: $effectiveType,
            maxDailyGuests: $command->maxDailyGuests ?? $property->getMaxDailyGuests(),
            dailySingleBeds: $command->dailySingleBeds ?? $property->getDailySingleBeds(),
            dailyDoubleBeds: $command->dailyDoubleBeds ?? $property->getDailyDoubleBeds(),
            checkInTime: $command->checkInTime ?? $property->getCheckInTime(),
            checkOutTime: $command->checkOutTime ?? $property->getCheckOutTime(),
        );
        $effectiveRoomsInDeal = $command->roomsInDeal ?? $property->getRoomsInDeal();
        $effectiveRoomsArea = $command->roomsArea ?? $property->getRoomsArea();
        RoomDealDetailsValidator::assertValid(
            dealType: $effectiveDealType,
            propertyType: $effectiveType,
            roomsInDeal: $effectiveRoomsInDeal,
            roomsArea: $effectiveRoomsArea,
        );
        PropertyDealCombinationValidator::assertValid($effectiveDealType, $effectiveType);
        $this->assertAreaConstraints($effectiveType, $effectiveLandArea);

        $price = null;
        if ($command->priceAmount !== null) {
            $price = Price::fromAmount(
                $command->priceAmount,
                $command->priceCurrency ?? 'BYN'
            );
        }

        $address = null;
        if ($command->building !== null) {
            $address = Address::create($command->building, $command->block);
        }

        $coordinates = null;
        if ($command->latitude !== null && $command->longitude !== null) {
            $coordinates = Coordinates::create($command->latitude, $command->longitude);
        }

        if (in_array($property->getStatus(), ['published', 'rejected'], true)) {
            $revisionData = $this->buildRevisionData($command);
            $pendingRevision = $this->revisionRepository->findLatestByPropertyAndStatus(
                $property->getId()->getValue(),
                PropertyRevision::STATUS_PENDING
            );

            if ($pendingRevision !== null) {
                $pendingRevision->setData($revisionData);
                $this->revisionRepository->save($pendingRevision);
            } else {
                $revision = new PropertyRevision($property, $revisionData);
                $this->revisionRepository->save($revision);
            }

            return;
        }

        $property->update(
            type: $command->type,
            dealType: $command->dealType,
            title: $command->title,
            description: $command->description,
            price: $price,
            area: $command->area,
            landArea: $command->landArea,
            rooms: $command->rooms,
            floor: $command->floor,
            totalFloors: $command->totalFloors,
            bathrooms: $command->bathrooms,
            yearBuilt: $command->yearBuilt,
            renovation: $command->renovation,
            balcony: $command->balcony,
            livingArea: $command->livingArea,
            kitchenArea: $command->kitchenArea,
            roomsInDeal: $command->roomsInDeal,
            roomsArea: $command->roomsArea,
            dealConditions: $command->dealConditions,
            maxDailyGuests: $command->maxDailyGuests,
            dailySingleBeds: $command->dailySingleBeds,
            dailyDoubleBeds: $command->dailyDoubleBeds,
            checkInTime: $command->checkInTime,
            checkOutTime: $command->checkOutTime,
            address: $address,
            cityId: $command->cityId,
            streetId: $command->streetId,
            coordinates: $coordinates,
            images: $command->images,
            amenities: $command->amenities,
            sellerType: $command->sellerType,
        );

        if ($price !== null) {
            $property->setPriceByn(
                $this->exchangeRateService->calculatePriceByn(
                    $command->priceAmount,
                    $command->priceCurrency ?? $property->getPrice()->getCurrency()
                )
            );
        }

        $this->propertyRepository->save($property);
        $this->metroProximityCalculator->syncForProperty($property);
        $this->propertyRepository->save($property);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRevisionData(UpdatePropertyCommand $command): array
    {
        return array_filter([
            'type' => $command->type,
            'dealType' => $command->dealType,
            'title' => $command->title,
            'description' => $command->description,
            'priceAmount' => $command->priceAmount,
            'priceCurrency' => $command->priceCurrency,
            'area' => $command->area,
            'landArea' => $command->landArea,
            'rooms' => $command->rooms,
            'floor' => $command->floor,
            'totalFloors' => $command->totalFloors,
            'bathrooms' => $command->bathrooms,
            'yearBuilt' => $command->yearBuilt,
            'renovation' => $command->renovation,
            'balcony' => $command->balcony,
            'livingArea' => $command->livingArea,
            'kitchenArea' => $command->kitchenArea,
            'roomsInDeal' => $command->roomsInDeal,
            'roomsArea' => $command->roomsArea,
            'dealConditions' => $command->dealConditions,
            'maxDailyGuests' => $command->maxDailyGuests,
            'dailySingleBeds' => $command->dailySingleBeds,
            'dailyDoubleBeds' => $command->dailyDoubleBeds,
            'checkInTime' => $command->checkInTime,
            'checkOutTime' => $command->checkOutTime,
            'building' => $command->building,
            'block' => $command->block,
            'cityId' => $command->cityId,
            'streetId' => $command->streetId,
            'latitude' => $command->latitude,
            'longitude' => $command->longitude,
            'images' => $command->images,
            'amenities' => $command->amenities,
            'sellerType' => $command->sellerType,
        ], static fn(mixed $value): bool => $value !== null);
    }

    private function assertAreaConstraints(string $propertyType, ?float $landArea): void
    {
        if (in_array($propertyType, ['land', 'house', 'dacha'], true) && ($landArea === null || $landArea <= 0)) {
            throw new \InvalidArgumentException('Укажите площадь участка в сотках для участка, дома и дачи');
        }
    }
}
