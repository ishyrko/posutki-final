<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use App\Domain\Property\Enum\PropertyType;
use App\Domain\Shared\Exception\DomainException;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'property_placement_level_prices')]
#[ORM\UniqueConstraint(name: 'uniq_placement_level_apartment_city', columns: ['property_type', 'city_id', 'level'])]
#[ORM\UniqueConstraint(name: 'uniq_placement_level_house_region', columns: ['property_type', 'region_id', 'level'])]
#[ORM\Index(columns: ['city_id', 'is_active'], name: 'idx_placement_levels_city_active')]
#[ORM\Index(columns: ['region_id', 'is_active'], name: 'idx_placement_levels_region_active')]
class PropertyPlacementLevelPrice
{
    public const MIN_LEVEL = 1;
    public const MAX_LEVEL = 5;

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

    #[ORM\Column(type: 'integer')]
    private int $level;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $capacity = null;

    #[ORM\Column(type: 'integer', name: 'price_byn_per_month')]
    private int $priceBynPerMonth;

    #[ORM\Column(type: 'integer', name: 'sort_order', options: ['default' => 0])]
    private int $sortOrder = 0;

    #[ORM\Column(type: 'boolean', name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct(
        string $propertyType,
        ?int $cityId,
        ?int $regionId,
        int $level,
        int $priceBynPerMonth,
        ?int $capacity = null,
        int $sortOrder = 0,
        bool $isActive = true,
    ) {
        if (!in_array($propertyType, PropertyType::values(), true)) {
            throw new DomainException('Неизвестный тип недвижимости');
        }

        $this->propertyType = $propertyType;
        $this->cityId = $cityId !== null && $cityId > 0 ? $cityId : null;
        $this->regionId = $regionId !== null && $regionId > 0 ? $regionId : null;
        $this->level = $level;
        $this->priceBynPerMonth = $priceBynPerMonth;
        $this->capacity = $capacity;
        $this->sortOrder = $sortOrder;
        $this->isActive = $isActive;
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

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): void
    {
        $this->capacity = $capacity !== null && $capacity >= 0 ? $capacity : null;
    }

    public function getPriceBynPerMonth(): int
    {
        return $this->priceBynPerMonth;
    }

    public function setPriceBynPerMonth(int $priceBynPerMonth): void
    {
        $this->priceBynPerMonth = $priceBynPerMonth;
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

    public function getLabel(): string
    {
        return 'VIP ' . $this->level;
    }

    public function validate(): void
    {
        if ($this->level < self::MIN_LEVEL || $this->level > self::MAX_LEVEL) {
            throw new DomainException(sprintf('VIP-уровень должен быть от %d до %d', self::MIN_LEVEL, self::MAX_LEVEL));
        }
        if ($this->capacity !== null && $this->capacity < 0) {
            throw new DomainException('Лимит мест не может быть отрицательным');
        }

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
}
