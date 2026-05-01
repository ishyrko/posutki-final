<?php

declare(strict_types=1);

namespace App\Application\Command\Property\CreateProperty;

final class CreatePropertyCommand
{
    public function __construct(
        public readonly string $ownerId,
        public readonly string $type,
        public readonly string $dealType,
        public readonly string $title,
        public readonly string $description,
        public readonly int $priceAmount,
        public readonly string $priceCurrency,
        public readonly float $area,
        public readonly ?int $rooms,
        public readonly ?int $floor,
        public readonly ?int $totalFloors,
        public readonly string $building,
        public readonly int $cityId,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?float $landArea = null,
        public readonly ?int $bathrooms = null,
        public readonly ?int $yearBuilt = null,
        public readonly ?string $renovation = null,
        public readonly ?string $balcony = null,
        public readonly ?float $livingArea = null,
        public readonly ?float $kitchenArea = null,
        public readonly ?int $roomsInDeal = null,
        public readonly ?float $roomsArea = null,
        public readonly ?array $dealConditions = null,
        public readonly ?int $maxDailyGuests = null,
        public readonly ?int $dailySingleBeds = null,
        public readonly ?int $dailyDoubleBeds = null,
        public readonly ?string $checkInTime = null,
        public readonly ?string $checkOutTime = null,
        public readonly ?int $streetId = null,
        public readonly ?string $block = null,
        public readonly array $images = [],
        public readonly array $amenities = [],
        public readonly ?string $contactPhone = null,
        public readonly ?string $contactName = null,
        public readonly ?string $sellerType = null,
    ) {
    }
}
