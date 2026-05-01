<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use App\Domain\Shared\Exception\ConflictException;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\Property\ValueObject\{Price, Address, Coordinates};
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'properties')]
#[ORM\Index(columns: ['city_id', 'deal_type', 'type', 'status'])]
#[ORM\Index(columns: ['created_at'])]
class Property
{
    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue]
    private Id $id;

    #[ORM\Column(type: 'id')]
    private Id $ownerId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 50, name: 'deal_type')]
    private string $dealType;

    #[ORM\Column(type: 'string', length: 16, name: 'seller_type', nullable: true)]
    private ?string $sellerType = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'price')]
    private Price $price;

    #[ORM\Column(type: 'float')]
    private float $area;

    #[ORM\Column(type: 'float', nullable: true, name: 'land_area')]
    private ?float $landArea = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rooms = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $floor = null;

    #[ORM\Column(type: 'integer', name: 'total_floors', nullable: true)]
    private ?int $totalFloors = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $bathrooms = null;

    #[ORM\Column(type: 'integer', nullable: true, name: 'year_built')]
    private ?int $yearBuilt = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $renovation = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $balcony = null;

    #[ORM\Column(type: 'float', nullable: true, name: 'living_area')]
    private ?float $livingArea = null;

    #[ORM\Column(type: 'float', nullable: true, name: 'kitchen_area')]
    private ?float $kitchenArea = null;

    #[ORM\Column(type: 'integer', nullable: true, name: 'rooms_in_deal')]
    private ?int $roomsInDeal = null;

    #[ORM\Column(type: 'float', nullable: true, name: 'rooms_area')]
    private ?float $roomsArea = null;

    #[ORM\Column(type: 'json', nullable: true, name: 'deal_conditions')]
    private ?array $dealConditions = null;

    #[ORM\Column(type: 'integer', nullable: true, name: 'max_daily_guests')]
    private ?int $maxDailyGuests = null;

    #[ORM\Column(type: 'integer', nullable: true, name: 'daily_single_beds')]
    private ?int $dailySingleBeds = null;

    #[ORM\Column(type: 'integer', nullable: true, name: 'daily_double_beds')]
    private ?int $dailyDoubleBeds = null;

    #[ORM\Column(type: 'string', length: 5, nullable: true, name: 'check_in_time')]
    private ?string $checkInTime = null;

    #[ORM\Column(type: 'string', length: 5, nullable: true, name: 'check_out_time')]
    private ?string $checkOutTime = null;

    #[ORM\Column(type: 'address')]
    private Address $address;

    #[ORM\Column(type: 'integer', name: 'city_id')]
    private int $cityId;

    #[ORM\Column(type: 'integer', nullable: true, name: 'street_id')]
    private ?int $streetId = null;

    #[ORM\Column(type: 'coordinates')]
    private Coordinates $coordinates;

    #[ORM\Column(type: 'json')]
    private array $images = [];

    #[ORM\Column(type: 'json')]
    private array $amenities = [];

    #[ORM\Column(type: 'string', length: 20, nullable: true, name: 'contact_phone')]
    private ?string $contactPhone = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true, name: 'contact_name')]
    private ?string $contactName = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = 'draft';

    #[ORM\Column(type: 'text', nullable: true, name: 'moderation_comment')]
    private ?string $moderationComment = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'published_at')]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'boosted_at')]
    private ?\DateTimeImmutable $boostedAt = null;

    #[ORM\Column(type: 'integer', name: 'price_byn', nullable: true)]
    private ?int $priceByn = null;

    #[ORM\Column(type: 'integer', name: 'price_per_meter_byn', nullable: true)]
    private ?int $pricePerMeterByn = null;

    #[ORM\Column(type: 'integer')]
    private int $views = 0;

    #[ORM\Column(type: 'integer', name: 'phone_views')]
    private int $phoneViews = 0;

    #[ORM\Column(type: 'boolean', name: 'near_metro', options: ['default' => false])]
    private bool $nearMetro = false;

    /**
     * @var Collection<int, PropertyRevision>
     */
    #[ORM\OneToMany(mappedBy: 'property', targetEntity: PropertyRevision::class, cascade: ['persist', 'remove'])]
    private Collection $revisions;

    public function __construct(
        Id $ownerId,
        string $type,
        string $dealType,
        string $title,
        string $description,
        Price $price,
        float $area,
        ?int $rooms,
        ?int $floor,
        ?int $totalFloors,
        ?int $bathrooms,
        ?int $yearBuilt,
        ?string $renovation,
        ?string $balcony,
        ?float $livingArea,
        ?float $kitchenArea,
        ?array $dealConditions,
        ?int $maxDailyGuests,
        ?int $dailySingleBeds,
        ?int $dailyDoubleBeds,
        ?string $checkInTime,
        ?string $checkOutTime,
        Address $address,
        int $cityId,
        Coordinates $coordinates,
        ?float $landArea = null,
        ?int $streetId = null,
        array $images = [],
        array $amenities = [],
        string $status = 'draft',
        ?string $contactPhone = null,
        ?string $contactName = null,
        ?string $sellerType = null,
        ?int $roomsInDeal = null,
        ?float $roomsArea = null,
    ) {
        $this->ownerId = $ownerId;
        $this->type = $type;
        $this->dealType = $dealType;
        $this->sellerType = $dealType === 'daily' ? $sellerType : null;
        $this->title = $title;
        $this->description = $description;
        $this->price = $price;
        $this->area = $area;
        $this->landArea = $landArea;
        $this->rooms = $rooms;
        $this->floor = $floor;
        $this->totalFloors = $totalFloors;
        $this->bathrooms = $bathrooms;
        $this->yearBuilt = $yearBuilt;
        $this->renovation = $renovation;
        $this->balcony = $balcony;
        $this->livingArea = $livingArea;
        $this->kitchenArea = $kitchenArea;
        $this->roomsInDeal = $roomsInDeal;
        $this->roomsArea = $roomsArea;
        $this->dealConditions = $dealConditions;
        $this->maxDailyGuests = $maxDailyGuests;
        $this->dailySingleBeds = $dailySingleBeds;
        $this->dailyDoubleBeds = $dailyDoubleBeds;
        $this->checkInTime = $checkInTime;
        $this->checkOutTime = $checkOutTime;
        $this->address = $address;
        $this->cityId = $cityId;
        $this->streetId = $streetId;
        $this->coordinates = $coordinates;
        $this->images = $images;
        $this->amenities = $amenities;
        $this->status = $status;
        $this->contactPhone = $contactPhone;
        $this->contactName = $contactName;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->revisions = new ArrayCollection();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getOwnerId(): Id
    {
        return $this->ownerId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getArea(): float
    {
        return $this->area;
    }

    public function getLandArea(): ?float
    {
        return $this->landArea;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        if ($status !== 'rejected') {
            $this->moderationComment = null;
        }
        if ($status === 'published' && $this->publishedAt === null) {
            $this->publishedAt = new \DateTimeImmutable();
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getModerationComment(): ?string
    {
        return $this->moderationComment;
    }

    public function setModerationComment(?string $moderationComment): void
    {
        $value = $moderationComment !== null ? trim($moderationComment) : null;
        $this->moderationComment = $value === '' ? null : $value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getPriceByn(): ?int
    {
        return $this->priceByn;
    }

    public function setPriceByn(?int $priceByn): void
    {
        $this->priceByn = $priceByn;
        $this->pricePerMeterByn = ($priceByn !== null && $this->area > 0)
            ? (int) round($priceByn / $this->area)
            : null;
    }

    public function getPricePerMeterByn(): ?int
    {
        return $this->pricePerMeterByn;
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function getPhoneViews(): int
    {
        return $this->phoneViews;
    }

    public function isNearMetro(): bool
    {
        return $this->nearMetro;
    }

    public function setNearMetro(bool $nearMetro): void
    {
        $this->nearMetro = $nearMetro;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, PropertyRevision>
     */
    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    public function hasPendingRevision(): bool
    {
        return $this->getLatestPendingRevision() !== null;
    }

    public function getPendingRevisionStatus(): ?string
    {
        $pendingRevision = $this->getLatestPendingRevision();
        if ($pendingRevision !== null) {
            return $pendingRevision->getStatus();
        }

        $rejectedRevision = $this->getLatestRejectedRevision();
        return $rejectedRevision?->getStatus();
    }

    public function getPendingRevisionComment(): ?string
    {
        $pendingRevision = $this->getLatestPendingRevision();
        if ($pendingRevision !== null) {
            return $pendingRevision->getModerationComment();
        }

        $rejectedRevision = $this->getLatestRejectedRevision();
        return $rejectedRevision?->getModerationComment();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPendingRevisionData(): ?array
    {
        $pendingRevision = $this->getLatestPendingRevision();
        if ($pendingRevision !== null) {
            return $pendingRevision->getData();
        }

        $rejectedRevision = $this->getLatestRejectedRevision();
        return $rejectedRevision?->getData();
    }

    public function getPendingRevisionDataPretty(): ?string
    {
        $data = $this->getPendingRevisionData();
        if ($data === null) {
            return null;
        }

        return json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPendingRevisionFinalData(): ?array
    {
        $pendingData = $this->getPendingRevisionData();
        if ($pendingData === null) {
            return null;
        }

        return array_replace($this->getCurrentSnapshot(), $pendingData);
    }

    public function getPendingRevisionFinalDataPretty(): ?string
    {
        $data = $this->getPendingRevisionFinalData();
        if ($data === null) {
            return null;
        }

        return json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: null;
    }

    public function getPendingRevisionDiff(): ?string
    {
        $changes = $this->getPendingRevisionChanges();
        if ($changes === []) {
            return null;
        }

        $lines = [];
        foreach ($changes as $change) {
            $lines[] = sprintf(
                "%s\n- %s\n+ %s",
                $change['label'],
                $change['old'],
                $change['new']
            );
        }

        return $lines === [] ? 'Изменений не обнаружено' : implode("\n", $lines);
    }

    /**
     * @return array<int, array{field:string,label:string,old:string,new:string}>
     */
    public function getPendingRevisionChanges(): array
    {
        $pendingData = $this->getPendingRevisionData();
        if ($pendingData === null || $pendingData === []) {
            return [];
        }

        $labels = [
            'title' => 'Заголовок',
            'description' => 'Описание',
            'type' => 'Тип',
            'dealType' => 'Тип сделки',
            'priceAmount' => 'Цена',
            'priceCurrency' => 'Валюта',
            'area' => 'Площадь общая',
            'landArea' => 'Площадь участка',
            'rooms' => 'Комнаты',
            'bathrooms' => 'Санузлы',
            'floor' => 'Этаж',
            'totalFloors' => 'Этажей всего',
            'yearBuilt' => 'Год постройки',
            'renovation' => 'Ремонт',
            'balcony' => 'Балкон',
            'livingArea' => 'Жилая площадь',
            'kitchenArea' => 'Площадь кухни',
            'roomsInDeal' => 'Комнат в сделке',
            'roomsArea' => 'Площадь комнат в сделке',
            'dealConditions' => 'Условия сделки',
            'maxDailyGuests' => 'Максимум гостей',
            'dailySingleBeds' => 'Односпальных кроватей',
            'dailyDoubleBeds' => 'Двуспальных кроватей',
            'checkInTime' => 'Время заезда',
            'checkOutTime' => 'Время выезда',
            'building' => 'Дом',
            'block' => 'Корпус',
            'cityId' => 'Город',
            'streetId' => 'Улица',
            'latitude' => 'Широта',
            'longitude' => 'Долгота',
            'images' => 'Фото',
            'amenities' => 'Удобства',
            'contactPhone' => 'Телефон',
            'contactName' => 'Имя контакта',
        ];

        $currentSnapshot = $this->getCurrentSnapshot();

        $changes = [];
        foreach ($pendingData as $key => $newValue) {
            if (!array_key_exists($key, $currentSnapshot)) {
                continue;
            }

            $oldFormatted = $this->formatDiffValue($currentSnapshot[$key]);
            $newFormatted = $this->formatDiffValue($newValue);
            if ($oldFormatted === $newFormatted) {
                continue;
            }

            $changes[] = [
                'field' => (string) $key,
                'label' => $labels[$key] ?? (string) $key,
                'old' => $oldFormatted,
                'new' => $newFormatted,
            ];
        }

        return $changes;
    }

    /**
     * @return array<string, mixed>
     */
    private function getCurrentSnapshot(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'dealType' => $this->dealType,
            'priceAmount' => $this->price->getAmount(),
            'priceCurrency' => $this->price->getCurrency(),
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
            'dealConditions' => $this->dealConditions,
            'maxDailyGuests' => $this->maxDailyGuests,
            'dailySingleBeds' => $this->dailySingleBeds,
            'dailyDoubleBeds' => $this->dailyDoubleBeds,
            'checkInTime' => $this->checkInTime,
            'checkOutTime' => $this->checkOutTime,
            'building' => $this->address->getBuilding(),
            'block' => $this->address->getBlock(),
            'cityId' => $this->cityId,
            'streetId' => $this->streetId,
            'latitude' => $this->coordinates->getLatitude(),
            'longitude' => $this->coordinates->getLongitude(),
            'images' => $this->images,
            'amenities' => $this->amenities,
            'contactPhone' => $this->contactPhone,
            'contactName' => $this->contactName,
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

        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    public function getLatestPendingRevision(): ?PropertyRevision
    {
        return $this->getLatestRevisionByStatus(PropertyRevision::STATUS_PENDING);
    }

    public function getLatestRejectedRevision(): ?PropertyRevision
    {
        return $this->getLatestRevisionByStatus(PropertyRevision::STATUS_REJECTED);
    }

    private function getLatestRevisionByStatus(string $status): ?PropertyRevision
    {
        $filteredRevisions = $this->revisions
            ->filter(static fn(PropertyRevision $revision): bool => $revision->getStatus() === $status)
            ->toArray();

        if ($filteredRevisions === []) {
            return null;
        }

        usort(
            $filteredRevisions,
            static fn(PropertyRevision $a, PropertyRevision $b): int => $b->getCreatedAt() <=> $a->getCreatedAt()
        );

        return $filteredRevisions[0];
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getBoostedAt(): ?\DateTimeImmutable
    {
        return $this->boostedAt;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDealType(): string
    {
        return $this->dealType;
    }

    public function getSellerType(): ?string
    {
        return $this->sellerType;
    }

    public function getRooms(): ?int
    {
        return $this->rooms;
    }

    public function getFloor(): ?int
    {
        return $this->floor;
    }

    public function getTotalFloors(): ?int
    {
        return $this->totalFloors;
    }

    public function getBathrooms(): ?int
    {
        return $this->bathrooms;
    }

    public function getYearBuilt(): ?int
    {
        return $this->yearBuilt;
    }

    public function getRenovation(): ?string
    {
        return $this->renovation;
    }

    public function getBalcony(): ?string
    {
        return $this->balcony;
    }

    public function getLivingArea(): ?float
    {
        return $this->livingArea;
    }

    public function getKitchenArea(): ?float
    {
        return $this->kitchenArea;
    }

    public function getRoomsInDeal(): ?int
    {
        return $this->roomsInDeal;
    }

    public function getRoomsArea(): ?float
    {
        return $this->roomsArea;
    }

    public function getDealConditions(): ?array
    {
        return $this->dealConditions;
    }

    public function getMaxDailyGuests(): ?int
    {
        return $this->maxDailyGuests;
    }

    public function getDailySingleBeds(): ?int
    {
        return $this->dailySingleBeds;
    }

    public function getDailyDoubleBeds(): ?int
    {
        return $this->dailyDoubleBeds;
    }

    public function getCheckInTime(): ?string
    {
        return $this->checkInTime;
    }

    public function getCheckOutTime(): ?string
    {
        return $this->checkOutTime;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getCityId(): int
    {
        return $this->cityId;
    }

    public function getStreetId(): ?int
    {
        return $this->streetId;
    }

    public function getCoordinates(): Coordinates
    {
        return $this->coordinates;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function getAmenities(): array
    {
        return $this->amenities;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function publish(): void
    {
        if ($this->status === 'moderation') {
            throw new DomainException('Объявление уже на модерации');
        }
        if ($this->status === 'published') {
            throw new DomainException('Объявление уже опубликовано');
        }

        $this->status = 'moderation';
        $this->moderationComment = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Free catalog boost: once per calendar day (server timezone).
     */
    public function boost(): void
    {
        if ($this->status !== 'published') {
            throw new DomainException('Поднимать можно только опубликованные объявления');
        }

        $now = new \DateTimeImmutable();
        $eligibleFrom = $this->createdAt->modify('+1 day');
        if ($now < $eligibleFrom) {
            throw new DomainException(
                'Поднятие в топ доступно не ранее чем через 24 часа после создания объявления'
            );
        }

        $todayStart = new \DateTimeImmutable('today');
        if ($this->boostedAt !== null && $this->boostedAt >= $todayStart) {
            throw new ConflictException('Объявление уже поднималось сегодня');
        }

        $this->boostedAt = new \DateTimeImmutable();
        $this->updatedAt = $this->boostedAt;
    }

    public function approve(): void
    {
        if ($this->status !== 'moderation') {
            throw new DomainException('Одобрить можно только объявления на модерации');
        }

        $this->status = 'published';
        $this->moderationComment = null;
        $this->publishedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function reject(?string $comment = null): void
    {
        if ($this->status !== 'moderation') {
            throw new DomainException('Отклонить можно только объявления на модерации');
        }

        $this->status = 'rejected';
        $this->moderationComment = $comment !== null ? trim($comment) : null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function archive(): void
    {
        $this->status = 'archived';
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function incrementViews(): void
    {
        $this->views++;
    }

    public function incrementPhoneViews(): void
    {
        $this->phoneViews++;
    }

    public function updatePrice(Price $price): void
    {
        $this->price = $price;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addImage(string $imageUrl): void
    {
        $this->images[] = $imageUrl;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function update(
        ?string $type = null,
        ?string $dealType = null,
        ?string $title = null,
        ?string $description = null,
        ?Price $price = null,
        ?float $area = null,
        ?float $landArea = null,
        ?int $rooms = null,
        ?int $floor = null,
        ?int $totalFloors = null,
        ?int $bathrooms = null,
        ?int $yearBuilt = null,
        ?string $renovation = null,
        ?string $balcony = null,
        ?float $livingArea = null,
        ?float $kitchenArea = null,
        ?int $roomsInDeal = null,
        ?float $roomsArea = null,
        ?array $dealConditions = null,
        ?int $maxDailyGuests = null,
        ?int $dailySingleBeds = null,
        ?int $dailyDoubleBeds = null,
        ?string $checkInTime = null,
        ?string $checkOutTime = null,
        ?Address $address = null,
        ?int $cityId = null,
        ?int $streetId = null,
        ?Coordinates $coordinates = null,
        ?array $images = null,
        ?array $amenities = null,
        ?string $contactPhone = null,
        ?string $contactName = null,
        ?string $sellerType = null,
    ): void {
        if ($type !== null) $this->type = $type;
        if ($dealType !== null) $this->dealType = $dealType;
        if ($title !== null) $this->title = $title;
        if ($description !== null) $this->description = $description;
        if ($price !== null) $this->price = $price;
        if ($area !== null) $this->area = $area;
        if ($landArea !== null) $this->landArea = $landArea;
        if ($rooms !== null) $this->rooms = $rooms;
        if ($floor !== null) $this->floor = $floor;
        if ($totalFloors !== null) $this->totalFloors = $totalFloors;
        if ($bathrooms !== null) $this->bathrooms = $bathrooms;
        if ($yearBuilt !== null) $this->yearBuilt = $yearBuilt;
        if ($renovation !== null) $this->renovation = $renovation;
        if ($balcony !== null) $this->balcony = $balcony;
        if ($livingArea !== null) $this->livingArea = $livingArea;
        if ($kitchenArea !== null) $this->kitchenArea = $kitchenArea;
        if ($roomsInDeal !== null) $this->roomsInDeal = $roomsInDeal;
        if ($roomsArea !== null) $this->roomsArea = $roomsArea;
        if ($dealConditions !== null) $this->dealConditions = $dealConditions;
        if ($maxDailyGuests !== null) $this->maxDailyGuests = $maxDailyGuests;
        if ($dailySingleBeds !== null) $this->dailySingleBeds = $dailySingleBeds;
        if ($dailyDoubleBeds !== null) $this->dailyDoubleBeds = $dailyDoubleBeds;
        if ($checkInTime !== null) $this->checkInTime = $checkInTime;
        if ($checkOutTime !== null) $this->checkOutTime = $checkOutTime;
        if ($address !== null) $this->address = $address;
        if ($cityId !== null) $this->cityId = $cityId;
        if ($streetId !== null) $this->streetId = $streetId;
        if ($coordinates !== null) $this->coordinates = $coordinates;
        if ($contactPhone !== null) $this->contactPhone = $contactPhone;
        if ($contactName !== null) $this->contactName = $contactName;
        if ($images !== null) $this->images = $images;
        if ($amenities !== null) $this->amenities = $amenities;

        if ($dealType !== null && $dealType !== 'daily') {
            $this->maxDailyGuests = null;
            $this->dailySingleBeds = null;
            $this->dailyDoubleBeds = null;
            $this->checkInTime = null;
            $this->checkOutTime = null;
            $this->sellerType = null;
        }

        if ($sellerType !== null && $this->dealType === 'daily') {
            $this->sellerType = $sellerType;
        }

        if ($this->type !== 'room' || $this->dealType === 'daily') {
            $this->roomsInDeal = null;
            $this->roomsArea = null;
        }

        $this->updatedAt = new \DateTimeImmutable();
    }

    public function delete(): void
    {
        $this->status = 'deleted';
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isOwnedBy(string $userId): bool
    {
        return (string) $this->ownerId->getValue() === $userId;
    }
}
