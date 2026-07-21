<?php

declare(strict_types=1);

namespace App\Application\Command\Property\UpdateProperty;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyRevision;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\PropertyRevisionRepositoryInterface;
use App\Domain\Property\Validation\DailyRentDetailsValidator;
use App\Domain\Property\Validation\DealConditionsValidator;
use App\Domain\Property\Validation\PaymentMethodsValidator;
use App\Domain\Property\Validation\FloorTotalFloorsValidator;
use App\Domain\Property\Validation\PropertyDailyPriceValidator;
use App\Domain\Property\Validation\PropertyDealCombinationValidator;
use App\Domain\Property\Validation\PropertyImageLimitsValidator;
use App\Domain\Property\Validation\RoomDealDetailsValidator;
use App\Domain\Property\ValueObject\Address;
use App\Domain\Property\ValueObject\Coordinates;
use App\Domain\Property\ValueObject\Price;
use App\Domain\Shared\Exception\DomainException;
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

    public function __invoke(UpdatePropertyCommand $command): bool
    {
        $property = $this->propertyRepository->findById(Id::fromString($command->propertyId));

        if (!$property) {
            throw new \InvalidArgumentException('Объявление не найдено');
        }

        if (!$property->isOwnedBy($command->userId)) {
            throw new UnauthorizedException('Нет прав на изменение этого объявления');
        }

        if (in_array($property->getStatus(), ['archived', 'deleted'], true)) {
            throw new DomainException('Нельзя изменять удалённое или неактивное объявление');
        }

        if ($command->type !== null && $command->type !== $property->getType()) {
            throw new DomainException('Нельзя изменить тип недвижимости');
        }

        $effectiveFloor = $command->floor ?? $property->getFloor();
        $effectiveTotalFloors = $command->totalFloors ?? $property->getTotalFloors();
        FloorTotalFloorsValidator::assertValid($effectiveFloor, $effectiveTotalFloors);

        $effectiveDealType = $command->dealType ?? $property->getDealType();
        $effectiveType = $command->type ?? $property->getType();
        if ($command->dealConditions !== null) {
            DealConditionsValidator::assertValid($command->dealConditions, $effectiveDealType, $effectiveType);
        }
        if ($command->paymentMethods !== null) {
            PaymentMethodsValidator::assertValid($command->paymentMethods);
        }
        $effectiveLandArea = $command->landArea ?? $property->getLandArea();
        $effectiveMinStayDays = $effectiveDealType === 'daily'
            ? ($command->minStayDays ?? $property->getMinStayDays() ?? 1)
            : null;
        DailyRentDetailsValidator::assertValid(
            dealType: $effectiveDealType,
            propertyType: $effectiveType,
            maxDailyGuests: $command->maxDailyGuests ?? $property->getMaxDailyGuests(),
            dailySingleBeds: $command->dailySingleBeds ?? $property->getDailySingleBeds(),
            dailyDoubleBeds: $command->dailyDoubleBeds ?? $property->getDailyDoubleBeds(),
            checkInTime: $command->checkInTime ?? $property->getCheckInTime(),
            checkOutTime: $command->checkOutTime ?? $property->getCheckOutTime(),
            minStayDays: $effectiveMinStayDays,
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
        if ($command->images !== null) {
            PropertyImageLimitsValidator::assertValid($effectiveType, count($command->images));
        }
        $this->assertAreaConstraints($effectiveType, $effectiveLandArea);

        $effectivePriceAmount = $command->priceAmount ?? $property->getPrice()->getAmount();
        $effectivePriceCurrency = $command->priceCurrency ?? $property->getPrice()->getCurrency();
        $priceByn = $this->exchangeRateService->calculatePriceByn($effectivePriceAmount, $effectivePriceCurrency);
        PropertyDailyPriceValidator::assertValid($effectiveDealType, $effectiveType, $priceByn);

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
            if ($this->isOnlyPriceChange($command, $property)) {
                if ($price !== null) {
                    $property->update(price: $price);
                    $property->setPriceByn(
                        $this->exchangeRateService->calculatePriceByn(
                            $command->priceAmount,
                            $command->priceCurrency ?? $property->getPrice()->getCurrency()
                        )
                    );
                    $this->propertyRepository->save($property);
                }

                return false;
            }

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

            return true;
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
            paymentMethods: $command->paymentMethods,
            maxDailyGuests: $command->maxDailyGuests,
            dailySingleBeds: $command->dailySingleBeds,
            dailyDoubleBeds: $command->dailyDoubleBeds,
            checkInTime: $command->checkInTime,
            checkOutTime: $command->checkOutTime,
            minStayDays: $command->minStayDays,
            address: $address,
            cityId: $command->cityId,
            streetId: $command->streetId,
            streetName: $command->streetName,
            coordinates: $coordinates,
            images: $command->images,
            amenities: $command->amenities,
            sellerType: $command->sellerType,
            weekendPriceNegotiable: $command->weekendPriceNegotiable,
            additionalServices: $command->additionalServices,
            instagramUrl: $command->instagramUrl,
            websiteUrl: $command->websiteUrl,
            videoUrl: $command->videoUrl,
            externalCalendarUrls: $command->externalCalendarUrls,
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

        return false;
    }

    private function isOnlyPriceChange(UpdatePropertyCommand $command, Property $property): bool
    {
        $current = $this->buildPropertySnapshot($property);
        $proposed = $this->buildProposedSnapshot($command, $property);

        $changedFields = [];
        foreach ($current as $key => $oldValue) {
            $newValue = $proposed[$key] ?? null;
            if ($this->formatDiffValue($oldValue) !== $this->formatDiffValue($newValue)) {
                $changedFields[] = $key;
            }
        }

        if ($changedFields === []) {
            return false;
        }

        $priceFields = ['priceAmount', 'priceCurrency'];
        foreach ($changedFields as $field) {
            if (!in_array($field, $priceFields, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPropertySnapshot(Property $property): array
    {
        return [
            'title' => $property->getTitle(),
            'description' => $property->getDescription(),
            'type' => $property->getType(),
            'dealType' => $property->getDealType(),
            'priceAmount' => $property->getPrice()->getAmount(),
            'priceCurrency' => $property->getPrice()->getCurrency(),
            'area' => $property->getArea(),
            'landArea' => $property->getLandArea(),
            'rooms' => $property->getRooms(),
            'bathrooms' => $property->getBathrooms(),
            'floor' => $property->getFloor(),
            'totalFloors' => $property->getTotalFloors(),
            'yearBuilt' => $property->getYearBuilt(),
            'renovation' => $property->getRenovation(),
            'balcony' => $property->getBalcony(),
            'livingArea' => $property->getLivingArea(),
            'kitchenArea' => $property->getKitchenArea(),
            'roomsInDeal' => $property->getRoomsInDeal(),
            'roomsArea' => $property->getRoomsArea(),
            'dealConditions' => $property->getDealConditions(),
            'paymentMethods' => $property->getPaymentMethods(),
            'maxDailyGuests' => $property->getMaxDailyGuests(),
            'dailySingleBeds' => $property->getDailySingleBeds(),
            'dailyDoubleBeds' => $property->getDailyDoubleBeds(),
            'checkInTime' => $property->getCheckInTime(),
            'checkOutTime' => $property->getCheckOutTime(),
            'minStayDays' => $property->getMinStayDays(),
            'building' => $property->getAddress()->getBuilding(),
            'block' => $property->getAddress()->getBlock(),
            'cityId' => $property->getCityId(),
            'streetId' => $property->getStreetId(),
            'streetName' => $property->getStreetName(),
            'latitude' => $property->getCoordinates()->getLatitude(),
            'longitude' => $property->getCoordinates()->getLongitude(),
            'images' => $property->getImages(),
            'amenities' => $property->getAmenities(),
            'instagramUrl' => $property->getInstagramUrl(),
            'websiteUrl' => $property->getWebsiteUrl(),
            'videoUrl' => $property->getVideoUrl(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProposedSnapshot(UpdatePropertyCommand $command, Property $property): array
    {
        return [
            'title' => $command->title ?? $property->getTitle(),
            'description' => $command->description ?? $property->getDescription(),
            'type' => $command->type ?? $property->getType(),
            'dealType' => $command->dealType ?? $property->getDealType(),
            'priceAmount' => $command->priceAmount ?? $property->getPrice()->getAmount(),
            'priceCurrency' => $command->priceCurrency ?? $property->getPrice()->getCurrency(),
            'area' => $command->area ?? $property->getArea(),
            'landArea' => $command->landArea ?? $property->getLandArea(),
            'rooms' => $command->rooms ?? $property->getRooms(),
            'bathrooms' => $command->bathrooms ?? $property->getBathrooms(),
            'floor' => $command->floor ?? $property->getFloor(),
            'totalFloors' => $command->totalFloors ?? $property->getTotalFloors(),
            'yearBuilt' => $command->yearBuilt ?? $property->getYearBuilt(),
            'renovation' => $command->renovation ?? $property->getRenovation(),
            'balcony' => $command->balcony ?? $property->getBalcony(),
            'livingArea' => $command->livingArea ?? $property->getLivingArea(),
            'kitchenArea' => $command->kitchenArea ?? $property->getKitchenArea(),
            'roomsInDeal' => $command->roomsInDeal ?? $property->getRoomsInDeal(),
            'roomsArea' => $command->roomsArea ?? $property->getRoomsArea(),
            'dealConditions' => $command->dealConditions ?? $property->getDealConditions(),
            'paymentMethods' => $command->paymentMethods ?? $property->getPaymentMethods(),
            'maxDailyGuests' => $command->maxDailyGuests ?? $property->getMaxDailyGuests(),
            'dailySingleBeds' => $command->dailySingleBeds ?? $property->getDailySingleBeds(),
            'dailyDoubleBeds' => $command->dailyDoubleBeds ?? $property->getDailyDoubleBeds(),
            'checkInTime' => $command->checkInTime ?? $property->getCheckInTime(),
            'checkOutTime' => $command->checkOutTime ?? $property->getCheckOutTime(),
            'minStayDays' => $command->minStayDays ?? $property->getMinStayDays(),
            'building' => $command->building ?? $property->getAddress()->getBuilding(),
            'block' => $command->block ?? $property->getAddress()->getBlock(),
            'cityId' => $command->cityId ?? $property->getCityId(),
            'streetId' => $command->streetId ?? $property->getStreetId(),
            'streetName' => $command->streetName ?? $property->getStreetName(),
            'latitude' => $command->latitude ?? $property->getCoordinates()->getLatitude(),
            'longitude' => $command->longitude ?? $property->getCoordinates()->getLongitude(),
            'images' => $command->images ?? $property->getImages(),
            'amenities' => $command->amenities ?? $property->getAmenities(),
            'instagramUrl' => $command->instagramUrl ?? $property->getInstagramUrl(),
            'websiteUrl' => $command->websiteUrl ?? $property->getWebsiteUrl(),
            'videoUrl' => $command->videoUrl ?? $property->getVideoUrl(),
        ];
    }

    private function formatDiffValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode(
                $value,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ) ?: '[]';
        }

        if ($value === null || $value === '') {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
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
            'paymentMethods' => $command->paymentMethods,
            'maxDailyGuests' => $command->maxDailyGuests,
            'dailySingleBeds' => $command->dailySingleBeds,
            'dailyDoubleBeds' => $command->dailyDoubleBeds,
            'checkInTime' => $command->checkInTime,
            'checkOutTime' => $command->checkOutTime,
            'minStayDays' => $command->minStayDays,
            'building' => $command->building,
            'block' => $command->block,
            'cityId' => $command->cityId,
            'streetId' => $command->streetId,
            'streetName' => $command->streetName,
            'latitude' => $command->latitude,
            'longitude' => $command->longitude,
            'images' => $command->images,
            'amenities' => $command->amenities,
            'sellerType' => $command->sellerType,
            'weekendPriceNegotiable' => $command->weekendPriceNegotiable,
            'additionalServices' => $command->additionalServices,
            'instagramUrl' => $command->instagramUrl,
            'websiteUrl' => $command->websiteUrl,
            'videoUrl' => $command->videoUrl,
            'externalCalendarUrls' => $command->externalCalendarUrls,
        ], static fn(mixed $value): bool => $value !== null);
    }

    private function assertAreaConstraints(string $propertyType, ?float $landArea): void
    {
        if ($propertyType === 'house' && ($landArea === null || $landArea <= 0)) {
            throw new \InvalidArgumentException('Укажите площадь участка в сотках для дома');
        }
    }
}
