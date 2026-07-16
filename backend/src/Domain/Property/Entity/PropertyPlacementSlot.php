<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use App\Domain\Property\Enum\PropertyType;
use App\Domain\Shared\Exception\DomainException;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'property_placement_slots')]
#[ORM\Index(columns: ['city_id', 'is_active'], name: 'idx_placement_slots_city_active')]
#[ORM\Index(columns: ['region_id', 'is_active'], name: 'idx_placement_slots_region_active')]
class PropertyPlacementSlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 20, name: 'property_type')]
    private string $propertyType;

    #[ORM\Column(type: 'integer', nullable: true, name: 'city_id')]
    private ?int $cityId = null;

    #[ORM\Column(type: 'integer', nullable: true, name: 'region_id')]
    private ?int $regionId = null;

    #[ORM\Column(type: 'integer', name: 'rank_from')]
    private int $rankFrom;

    #[ORM\Column(type: 'integer', name: 'rank_to')]
    private int $rankTo;

    #[ORM\Column(type: 'integer')]
    private int $capacity;

    #[ORM\Column(type: 'integer', name: 'price_byn_per_month')]
    private int $priceBynPerMonth;

    #[ORM\Column(type: 'boolean', name: 'is_top_slot', options: ['default' => false])]
    private bool $isTopSlot = false;

    #[ORM\Column(type: 'integer', name: 'sort_order', options: ['default' => 0])]
    private int $sortOrder = 0;

    #[ORM\Column(type: 'boolean', name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct(
        string $propertyType,
        ?int $cityId,
        ?int $regionId,
        int $rankFrom,
        int $rankTo,
        int $priceBynPerMonth,
        bool $isTopSlot = false,
        int $sortOrder = 0,
        bool $isActive = true,
    ) {
        if (!in_array($propertyType, PropertyType::values(), true)) {
            throw new DomainException('Неизвестный тип недвижимости');
        }

        $this->propertyType = $propertyType;
        $this->cityId = $cityId !== null && $cityId > 0 ? $cityId : null;
        $this->regionId = $regionId !== null && $regionId > 0 ? $regionId : null;
        $this->rankFrom = $rankFrom;
        $this->rankTo = $rankTo;
        $this->priceBynPerMonth = $priceBynPerMonth;
        $this->isTopSlot = $isTopSlot;
        $this->sortOrder = $sortOrder;
        $this->isActive = $isActive;
        $this->syncCapacityFromRanks();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPropertyType(): string
    {
        return $this->propertyType;
    }

    public function setPropertyType(string $propertyType): void
    {
        if (!in_array($propertyType, PropertyType::values(), true)) {
            throw new DomainException('Неизвестный тип недвижимости');
        }

        $this->propertyType = $propertyType;
    }

    public function getCityId(): ?int
    {
        return $this->cityId;
    }

    public function setCityId(?int $cityId): void
    {
        $this->cityId = $cityId !== null && $cityId > 0 ? $cityId : null;
    }

    public function getRegionId(): ?int
    {
        return $this->regionId;
    }

    public function setRegionId(?int $regionId): void
    {
        $this->regionId = $regionId !== null && $regionId > 0 ? $regionId : null;
    }

    public function getRankFrom(): int
    {
        return $this->rankFrom;
    }

    public function setRankFrom(int $rankFrom): void
    {
        $this->rankFrom = $rankFrom;
        $this->syncCapacityFromRanks();
    }

    public function getRankTo(): int
    {
        return $this->rankTo;
    }

    public function setRankTo(int $rankTo): void
    {
        $this->rankTo = $rankTo;
        $this->syncCapacityFromRanks();
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function getPriceBynPerMonth(): int
    {
        return $this->priceBynPerMonth;
    }

    public function setPriceBynPerMonth(int $priceBynPerMonth): void
    {
        $this->priceBynPerMonth = $priceBynPerMonth;
    }

    public function isTopSlot(): bool
    {
        return $this->isTopSlot;
    }

    public function setIsTopSlot(bool $isTopSlot): void
    {
        $this->isTopSlot = $isTopSlot;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function isForApartments(): bool
    {
        return $this->propertyType === PropertyType::Apartment->value;
    }

    public function isForHouses(): bool
    {
        return $this->propertyType === PropertyType::House->value;
    }

    public function validate(): void
    {
        if ($this->rankFrom < 1 || $this->rankTo < 1) {
            throw new DomainException('Позиции должны быть не меньше 1');
        }
        if ($this->rankFrom > $this->rankTo) {
            throw new DomainException('Позиция «с» не может быть больше «по»');
        }

        $this->syncCapacityFromRanks();

        if ($this->propertyType === PropertyType::Apartment->value) {
            if ($this->cityId === null) {
                throw new DomainException('Для квартир нужно выбрать город');
            }
            if ($this->regionId !== null) {
                throw new DomainException('Для квартир область не указывается');
            }

            return;
        }

        if ($this->propertyType === PropertyType::House->value) {
            if ($this->regionId === null) {
                throw new DomainException('Для домов нужно выбрать область');
            }
            if ($this->cityId !== null) {
                throw new DomainException('Для домов город не указывается');
            }
        }
    }

    public function getLabel(): string
    {
        if ($this->rankFrom === $this->rankTo) {
            return (string) $this->rankFrom;
        }

        return $this->rankFrom . '-' . $this->rankTo;
    }

    private function syncCapacityFromRanks(): void
    {
        if ($this->rankFrom < 1 || $this->rankTo < 1 || $this->rankFrom > $this->rankTo) {
            $this->capacity = 0;

            return;
        }

        $this->capacity = $this->rankTo - $this->rankFrom + 1;
    }
}
