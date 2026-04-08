<?php

declare(strict_types=1);

namespace App\Application\Command\Property\CreateProperty;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Event\PropertySubmittedForModerationEvent;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Validation\DailyRentDetailsValidator;
use App\Domain\Property\Validation\DealConditionsValidator;
use App\Domain\Property\Validation\FloorTotalFloorsValidator;
use App\Domain\Property\Validation\PropertyDealCombinationValidator;
use App\Domain\Property\Validation\RoomDealDetailsValidator;
use App\Domain\Property\ValueObject\{Price, Address, Coordinates};
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;
use App\Infrastructure\Service\ExchangeRateService;
use App\Infrastructure\Service\MetroProximityCalculator;
use Symfony\Component\Messenger\MessageBusInterface;

final class CreatePropertyHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly UserPhoneRepositoryInterface $userPhoneRepository,
        private readonly ExchangeRateService $exchangeRateService,
        private readonly MetroProximityCalculator $metroProximityCalculator,
        private readonly MessageBusInterface $notificationBus,
    ) {
    }

    public function __invoke(CreatePropertyCommand $command): int
    {
        DealConditionsValidator::assertValid($command->dealConditions, $command->dealType, $command->type);
        DailyRentDetailsValidator::assertValid(
            dealType: $command->dealType,
            propertyType: $command->type,
            maxDailyGuests: $command->maxDailyGuests,
            dailyBedCount: $command->dailyBedCount,
            checkInTime: $command->checkInTime,
            checkOutTime: $command->checkOutTime,
        );
        RoomDealDetailsValidator::assertValid(
            dealType: $command->dealType,
            propertyType: $command->type,
            roomsInDeal: $command->roomsInDeal,
            roomsArea: $command->roomsArea,
        );
        PropertyDealCombinationValidator::assertValid($command->dealType, $command->type);
        FloorTotalFloorsValidator::assertValid($command->floor, $command->totalFloors);
        $this->assertAreaConstraints($command->type, $command->area, $command->landArea);

        if ($command->contactPhone !== null) {
            $ownerId = Id::fromString($command->ownerId);
            $userPhone = $this->userPhoneRepository->findByUserIdAndPhone($ownerId, $command->contactPhone);

            if ($userPhone === null || !$userPhone->isVerified()) {
                throw new DomainException('Подтвердите контактный телефон');
            }
        }

        $ownerId = Id::fromString($command->ownerId);
        $price = Price::fromAmount($command->priceAmount, $command->priceCurrency);
        $address = Address::create($command->building, $command->block);
        $coordinates = Coordinates::create($command->latitude, $command->longitude);

        $property = new Property(
            ownerId: $ownerId,
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
            dealConditions: $command->dealConditions,
            maxDailyGuests: $command->maxDailyGuests,
            dailyBedCount: $command->dailyBedCount,
            checkInTime: $command->checkInTime,
            checkOutTime: $command->checkOutTime,
            address: $address,
            cityId: $command->cityId,
            coordinates: $coordinates,
            streetId: $command->streetId,
            images: $command->images,
            amenities: $command->amenities,
            contactPhone: $command->contactPhone,
            contactName: $command->contactName,
            roomsInDeal: $command->roomsInDeal,
            roomsArea: $command->roomsArea,
        );
        $property->publish();

        $property->setPriceByn(
            $this->exchangeRateService->calculatePriceByn($command->priceAmount, $command->priceCurrency)
        );

        $this->propertyRepository->save($property);
        $this->metroProximityCalculator->syncForProperty($property);
        $this->propertyRepository->save($property);

        $this->notificationBus->dispatch(new PropertySubmittedForModerationEvent((string) $property->getId()->getValue()));

        return $property->getId()->getValue();
    }

    private function assertAreaConstraints(string $propertyType, float $area, ?float $landArea): void
    {
        if ($propertyType === 'land' && $area !== 0.0) {
            throw new DomainException('Для участков площадь в м² должна быть 0');
        }

        if (in_array($propertyType, ['land', 'house', 'dacha'], true) && ($landArea === null || $landArea <= 0)) {
            throw new DomainException('Укажите площадь участка в сотках для участка, дома и дачи');
        }
    }
}
