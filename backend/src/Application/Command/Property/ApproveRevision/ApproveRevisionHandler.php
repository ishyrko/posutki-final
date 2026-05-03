<?php

declare(strict_types=1);

namespace App\Application\Command\Property\ApproveRevision;

use App\Domain\Property\Entity\PropertyRevision;
use App\Domain\Property\Event\PropertyApprovedEvent;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\PropertyRevisionRepositoryInterface;
use App\Domain\Property\Validation\DailyRentDetailsValidator;
use App\Domain\Property\Validation\DealConditionsValidator;
use App\Domain\Property\Validation\PropertyDealCombinationValidator;
use App\Domain\Property\Validation\RoomDealDetailsValidator;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Service\ExchangeRateService;
use App\Infrastructure\Service\MetroProximityCalculator;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class ApproveRevisionHandler
{
    public function __construct(
        private PropertyRepositoryInterface $propertyRepository,
        private PropertyRevisionRepositoryInterface $revisionRepository,
        private ExchangeRateService $exchangeRateService,
        private MetroProximityCalculator $metroProximityCalculator,
        private MessageBusInterface $notificationBus,
    ) {
    }

    public function __invoke(ApproveRevisionCommand $command): void
    {
        $property = $this->propertyRepository->findById(Id::fromString($command->propertyId));
        if ($property === null) {
            throw new \InvalidArgumentException('Объявление не найдено');
        }

        $revision = $this->revisionRepository->findById(Id::fromString($command->revisionId)->getValue());
        if ($revision === null) {
            throw new \InvalidArgumentException('Версия не найдена');
        }

        if ($revision->getProperty()->getId()->getValue() !== $property->getId()->getValue()) {
            throw new \InvalidArgumentException('Версия не относится к этому объявлению');
        }

        $data = $revision->getData();
        $priceAmount = isset($data['priceAmount']) ? (int) $data['priceAmount'] : null;
        $priceCurrency = isset($data['priceCurrency']) ? (string) $data['priceCurrency'] : null;
        $price = $priceAmount !== null ? Price::fromAmount($priceAmount, $priceCurrency ?? 'BYN') : null;

        $address = isset($data['building'])
            ? Address::create(
                (string) $data['building'],
                isset($data['block']) ? (string) $data['block'] : null
            )
            : null;

        $coordinates = isset($data['latitude'], $data['longitude'])
            ? Coordinates::create((float) $data['latitude'], (float) $data['longitude'])
            : null;

        $effectiveDealType = isset($data['dealType']) ? (string) $data['dealType'] : $property->getDealType();
        $effectiveType = isset($data['type']) ? (string) $data['type'] : $property->getType();
        if (isset($data['dealConditions']) && is_array($data['dealConditions'])) {
            DealConditionsValidator::assertValid($data['dealConditions'], $effectiveDealType, $effectiveType);
        }
        $effectiveLandArea = isset($data['landArea']) ? (float) $data['landArea'] : $property->getLandArea();
        DailyRentDetailsValidator::assertValid(
            dealType: $effectiveDealType,
            propertyType: $effectiveType,
            maxDailyGuests: isset($data['maxDailyGuests']) ? (int) $data['maxDailyGuests'] : $property->getMaxDailyGuests(),
            dailySingleBeds: array_key_exists('dailySingleBeds', $data) ? (int) $data['dailySingleBeds'] : $property->getDailySingleBeds(),
            dailyDoubleBeds: array_key_exists('dailyDoubleBeds', $data) ? (int) $data['dailyDoubleBeds'] : $property->getDailyDoubleBeds(),
            checkInTime: isset($data['checkInTime']) ? (string) $data['checkInTime'] : $property->getCheckInTime(),
            checkOutTime: isset($data['checkOutTime']) ? (string) $data['checkOutTime'] : $property->getCheckOutTime(),
        );
        $effectiveRoomsInDeal = isset($data['roomsInDeal']) ? (int) $data['roomsInDeal'] : $property->getRoomsInDeal();
        $effectiveRoomsArea = isset($data['roomsArea']) ? (float) $data['roomsArea'] : $property->getRoomsArea();
        RoomDealDetailsValidator::assertValid(
            dealType: $effectiveDealType,
            propertyType: $effectiveType,
            roomsInDeal: $effectiveRoomsInDeal,
            roomsArea: $effectiveRoomsArea,
        );
        PropertyDealCombinationValidator::assertValid($effectiveDealType, $effectiveType);
        $this->assertAreaConstraints($effectiveType, $effectiveLandArea);

        $property->update(
            type: isset($data['type']) ? (string) $data['type'] : null,
            dealType: isset($data['dealType']) ? (string) $data['dealType'] : null,
            title: isset($data['title']) ? (string) $data['title'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            price: $price,
            area: isset($data['area']) ? (float) $data['area'] : null,
            landArea: isset($data['landArea']) ? (float) $data['landArea'] : null,
            rooms: isset($data['rooms']) ? (int) $data['rooms'] : null,
            floor: isset($data['floor']) ? (int) $data['floor'] : null,
            totalFloors: isset($data['totalFloors']) ? (int) $data['totalFloors'] : null,
            bathrooms: isset($data['bathrooms']) ? (int) $data['bathrooms'] : null,
            yearBuilt: isset($data['yearBuilt']) ? (int) $data['yearBuilt'] : null,
            renovation: isset($data['renovation']) ? (string) $data['renovation'] : null,
            balcony: isset($data['balcony']) ? (string) $data['balcony'] : null,
            livingArea: isset($data['livingArea']) ? (float) $data['livingArea'] : null,
            kitchenArea: isset($data['kitchenArea']) ? (float) $data['kitchenArea'] : null,
            roomsInDeal: isset($data['roomsInDeal']) ? (int) $data['roomsInDeal'] : null,
            roomsArea: isset($data['roomsArea']) ? (float) $data['roomsArea'] : null,
            dealConditions: isset($data['dealConditions']) && is_array($data['dealConditions']) ? $data['dealConditions'] : null,
            maxDailyGuests: isset($data['maxDailyGuests']) ? (int) $data['maxDailyGuests'] : null,
            dailySingleBeds: array_key_exists('dailySingleBeds', $data) ? (int) $data['dailySingleBeds'] : null,
            dailyDoubleBeds: array_key_exists('dailyDoubleBeds', $data) ? (int) $data['dailyDoubleBeds'] : null,
            checkInTime: isset($data['checkInTime']) ? (string) $data['checkInTime'] : null,
            checkOutTime: isset($data['checkOutTime']) ? (string) $data['checkOutTime'] : null,
            address: $address,
            cityId: isset($data['cityId']) ? (int) $data['cityId'] : null,
            streetId: array_key_exists('streetId', $data) && $data['streetId'] !== null ? (int) $data['streetId'] : null,
            coordinates: $coordinates,
            images: isset($data['images']) && is_array($data['images']) ? $data['images'] : null,
            amenities: isset($data['amenities']) && is_array($data['amenities']) ? $data['amenities'] : null,
            sellerType: isset($data['sellerType']) ? (string) $data['sellerType'] : null,
        );

        if ($priceAmount !== null) {
            $property->setPriceByn(
                $this->exchangeRateService->calculatePriceByn(
                    $priceAmount,
                    $priceCurrency ?? 'BYN'
                )
            );
        }

        // Rejected listings become published after approved correction.
        if ($property->getStatus() === 'rejected') {
            $property->setStatus('published');
        }

        $revision->approve();

        $this->propertyRepository->save($property);
        $this->revisionRepository->save($revision);
        $this->metroProximityCalculator->syncForProperty($property);
        $this->propertyRepository->save($property);

        $this->notificationBus->dispatch(new PropertyApprovedEvent($command->propertyId));
    }

    private function assertAreaConstraints(string $propertyType, ?float $landArea): void
    {
        if (in_array($propertyType, ['land', 'house', 'dacha'], true) && ($landArea === null || $landArea <= 0)) {
            throw new \InvalidArgumentException('Укажите площадь участка в сотках для участка, дома и дачи');
        }
    }
}
