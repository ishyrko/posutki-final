<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\Street;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Enum\DealType;

final class PropertyDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly int $ownerId,
        public readonly string $type,
        public readonly string $typeLabel,
        public readonly string $dealType,
        public readonly string $dealTypeLabel,
        public readonly ?string $sellerType,
        public readonly string $title,
        public readonly string $description,
        public readonly int $priceAmount,
        public readonly string $priceCurrency,
        public readonly ?int $priceByn,
        public readonly float $area,
        public readonly ?float $landArea,
        public readonly ?int $rooms,
        public readonly ?int $floor,
        public readonly ?int $totalFloors,
        public readonly ?int $bathrooms,
        public readonly ?int $yearBuilt,
        public readonly ?string $renovation,
        public readonly ?string $balcony,
        public readonly ?float $livingArea,
        public readonly ?float $kitchenArea,
        public readonly ?int $roomsInDeal,
        public readonly ?float $roomsArea,
        public readonly ?array $dealConditions,
        public readonly ?int $maxDailyGuests,
        public readonly ?int $dailySingleBeds,
        public readonly ?int $dailyDoubleBeds,
        public readonly ?string $checkInTime,
        public readonly ?string $checkOutTime,
        public readonly string $building,
        public readonly ?string $block,
        public readonly int $cityId,
        public readonly string $cityName,
        public readonly string $citySlug,
        public readonly ?int $streetId,
        public readonly ?string $streetName,
        public readonly ?string $districtName,
        public readonly ?string $regionName,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly array $images,
        public readonly array $amenities,
        public readonly string $status,
        public readonly ?string $moderationComment,
        public readonly ?string $pendingRevisionStatus,
        public readonly ?string $pendingRevisionComment,
        /** @var array<string, mixed>|null */
        public readonly ?array $pendingRevisionData,
        public readonly ?string $contactPhone,
        public readonly ?string $contactName,
        /** @var array<string, mixed>|null Owner legal profile for daily listings (public). */
        public readonly ?array $dailySellerLegalProfile,
        public readonly bool $nearMetro,
        /** @var array<int, array{id:int,name:string,slug:string,line:int,distanceKm:float}> */
        public readonly array $nearbyMetroStations,
        public readonly int $views,
        public readonly int $phoneViews,
        public readonly int $favoritesCount,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $publishedAt = null,
        public readonly ?\DateTimeImmutable $boostedAt = null,
    ) {
    }

    public static function fromEntity(
        Property $property,
        City $city,
        ?Street $street = null,
        array $nearbyMetroStations = [],
        int $favoritesCount = 0,
        ?array $dailySellerLegalProfile = null,
    ): self
    {
        $district = $city->getRegionDistrict();
        $region = $district?->getRegion();

        return new self(
            id: $property->getId()->getValue(),
            ownerId: $property->getOwnerId()->getValue(),
            type: $property->getType(),
            typeLabel: PropertyType::tryFrom($property->getType())?->label() ?? $property->getType(),
            dealType: $property->getDealType(),
            dealTypeLabel: DealType::tryFrom($property->getDealType())?->label() ?? $property->getDealType(),
            sellerType: $property->getSellerType(),
            title: $property->getTitle(),
            description: $property->getDescription(),
            priceAmount: $property->getPrice()->getAmount(),
            priceCurrency: $property->getPrice()->getCurrency(),
            priceByn: $property->getPriceByn(),
            area: $property->getArea(),
            landArea: $property->getLandArea(),
            rooms: $property->getRooms(),
            floor: $property->getFloor(),
            totalFloors: $property->getTotalFloors(),
            bathrooms: $property->getBathrooms(),
            yearBuilt: $property->getYearBuilt(),
            renovation: $property->getRenovation(),
            balcony: $property->getBalcony(),
            livingArea: $property->getLivingArea(),
            kitchenArea: $property->getKitchenArea(),
            roomsInDeal: $property->getRoomsInDeal(),
            roomsArea: $property->getRoomsArea(),
            dealConditions: $property->getDealConditions(),
            maxDailyGuests: $property->getMaxDailyGuests(),
            dailySingleBeds: $property->getDailySingleBeds(),
            dailyDoubleBeds: $property->getDailyDoubleBeds(),
            checkInTime: $property->getCheckInTime(),
            checkOutTime: $property->getCheckOutTime(),
            building: $property->getAddress()->getBuilding(),
            block: $property->getAddress()->getBlock(),
            cityId: $property->getCityId(),
            cityName: $city->getName(),
            citySlug: $city->getSlug(),
            streetId: $property->getStreetId(),
            streetName: $street?->getName(),
            districtName: $district?->getName(),
            regionName: $region?->getName(),
            latitude: $property->getCoordinates()->getLatitude(),
            longitude: $property->getCoordinates()->getLongitude(),
            images: $property->getImages(),
            amenities: $property->getAmenities(),
            status: $property->getStatus(),
            moderationComment: $property->getModerationComment(),
            pendingRevisionStatus: $property->getPendingRevisionStatus(),
            pendingRevisionComment: $property->getPendingRevisionComment(),
            pendingRevisionData: $property->getPendingRevisionData(),
            contactPhone: $property->getContactPhone(),
            contactName: $property->getContactName(),
            dailySellerLegalProfile: $dailySellerLegalProfile,
            nearMetro: $property->isNearMetro(),
            nearbyMetroStations: $nearbyMetroStations,
            views: $property->getViews(),
            phoneViews: $property->getPhoneViews(),
            favoritesCount: $favoritesCount,
            createdAt: $property->getCreatedAt(),
            publishedAt: $property->getPublishedAt(),
            boostedAt: $property->getBoostedAt(),
        );
    }

    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'ownerId' => $this->ownerId,
            'type' => $this->type,
            'typeLabel' => $this->typeLabel,
            'dealType' => $this->dealType,
            'dealTypeLabel' => $this->dealTypeLabel,
            'sellerType' => $this->sellerType,
            'status' => $this->status,
            'title' => $this->title,
            'description' => $this->description,
            'price' => [
                'amount' => $this->priceAmount,
                'currency' => $this->priceCurrency,
            ],
            'priceByn' => $this->priceByn,
            'address' => [
                'regionName' => $this->regionName,
                'districtName' => $this->districtName,
                'cityId' => $this->cityId,
                'cityName' => $this->cityName,
                'citySlug' => $this->citySlug,
                'streetId' => $this->streetId,
                'streetName' => $this->streetName,
                'building' => $this->building,
                'block' => $this->block,
            ],
            'specifications' => [
                'area' => $this->area,
                'landArea' => $this->landArea,
                'rooms' => $this->rooms,
                'bathrooms' => $this->bathrooms,
                'floor' => $this->floor,
                'totalFloors' => $this->totalFloors,
                'yearBuilt' => $this->yearBuilt,
                'renovation' => $this->renovation,
                'balcony' => $this->balcony,
                'livingArea' => $this->livingArea,
                'kitchenArea' => $this->kitchenArea,
                'roomsInDeal' => $this->roomsInDeal,
                'roomsArea' => $this->roomsArea,
                'dealConditions' => $this->dealConditions ?? [],
                'maxDailyGuests' => $this->maxDailyGuests,
                'dailySingleBeds' => $this->dailySingleBeds,
                'dailyDoubleBeds' => $this->dailyDoubleBeds,
                'checkInTime' => $this->checkInTime,
                'checkOutTime' => $this->checkOutTime,
            ],
            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'images' => array_values(array_map(
                fn(string $url, int $i) => [
                    'id' => $i,
                    'url' => $url,
                    'thumbnailUrl' => $this->buildThumbnailUrl($url),
                ],
                $this->images,
                array_keys($this->images),
            )),
            'amenities' => $this->amenities,
            'moderationComment' => $this->moderationComment,
            'pendingRevisionStatus' => $this->pendingRevisionStatus,
            'pendingRevisionComment' => $this->pendingRevisionComment,
            'pendingRevisionData' => $this->pendingRevisionData,
            'contact' => [
                'phone' => $this->contactPhone,
                'name' => $this->contactName,
            ],
            'nearMetro' => $this->nearMetro,
            'nearbyMetroStations' => $this->nearbyMetroStations,
            'views' => $this->views,
            'phoneViews' => $this->phoneViews,
            'favoritesCount' => $this->favoritesCount,
            'createdAt' => $this->createdAt->format('c'),
            'publishedAt' => $this->publishedAt?->format('c'),
            'boostedAt' => $this->boostedAt?->format('c'),
        ];

        if ($this->dailySellerLegalProfile !== null) {
            $data['dailySellerLegalProfile'] = $this->dailySellerLegalProfile;
        }

        return $data;
    }

    private function buildThumbnailUrl(string $url): ?string
    {
        if (!str_starts_with($url, '/uploads/')) {
            return null;
        }

        if (str_contains($url, '/thumbs/')) {
            return $url;
        }

        $relativePath = ltrim(substr($url, strlen('/uploads/')), '/');
        if ($relativePath === '') {
            return null;
        }

        $segments = explode('/', $relativePath);
        $fileName = array_pop($segments);
        if (!is_string($fileName) || $fileName === '') {
            return null;
        }

        if ($segments === []) {
            // Backward compatibility for old files in /uploads/<file>
            return '/uploads/thumbs/' . $fileName;
        }

        return '/uploads/' . implode('/', $segments) . '/thumbs/' . $fileName;
    }

}
