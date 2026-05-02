<?php

declare(strict_types=1);

namespace App\Application\Command\Property\UpdateProperty;

readonly class UpdatePropertyCommand
{
    public function __construct(
        public string $propertyId,
        public string $userId,
        public ?string $type = null,
        public ?string $dealType = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?int $priceAmount = null,
        public ?string $priceCurrency = null,
        public ?float $area = null,
        public ?float $landArea = null,
        public ?int $rooms = null,
        public ?int $floor = null,
        public ?int $totalFloors = null,
        public ?int $bathrooms = null,
        public ?int $yearBuilt = null,
        public ?string $renovation = null,
        public ?string $balcony = null,
        public ?float $livingArea = null,
        public ?float $kitchenArea = null,
        public ?int $roomsInDeal = null,
        public ?float $roomsArea = null,
        public ?array $dealConditions = null,
        public ?int $maxDailyGuests = null,
        public ?int $dailySingleBeds = null,
        public ?int $dailyDoubleBeds = null,
        public ?string $checkInTime = null,
        public ?string $checkOutTime = null,
        public ?string $building = null,
        public ?string $block = null,
        public ?int $cityId = null,
        public ?int $streetId = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?array $images = null,
        public ?array $amenities = null,
        public ?string $sellerType = null,
    ) {
    }
}
