<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use App\Domain\Property\Enum\PropertyType;
use App\Domain\Shared\Exception\DomainException;
use Doctrine\ORM\Mapping as ORM;

/**
 * Per-city (apartments) / per-region (houses) VIP settings: the highest configurable VIP level.
 * Boost cost is derived from level tariffs (see PropertyPlacementService::quoteBoostPurchase).
 */
#[ORM\Entity]
#[ORM\Table(name: 'property_placement_scope_settings')]
#[ORM\UniqueConstraint(name: 'uniq_placement_scope_apartment_city', columns: ['property_type', 'city_id'])]
#[ORM\UniqueConstraint(name: 'uniq_placement_scope_house_region', columns: ['property_type', 'region_id'])]
class PropertyPlacementScopeSettings
{
    public const DEFAULT_MAX_LEVEL = 5;

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

    #[ORM\Column(type: 'integer', name: 'max_level', options: ['default' => self::DEFAULT_MAX_LEVEL])]
    private int $maxLevel = self::DEFAULT_MAX_LEVEL;

    #[ORM\Column(type: 'boolean', name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct(
        string $propertyType,
        ?int $cityId,
        ?int $regionId,
        int $maxLevel = self::DEFAULT_MAX_LEVEL,
        bool $isActive = true,
    ) {
        if (!in_array($propertyType, PropertyType::values(), true)) {
            throw new DomainException('Неизвестный тип недвижимости');
        }

        $this->propertyType = $propertyType;
        $this->cityId = $cityId !== null && $cityId > 0 ? $cityId : null;
        $this->regionId = $regionId !== null && $regionId > 0 ? $regionId : null;
        $this->maxLevel = $maxLevel;
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

    public function getMaxLevel(): int
    {
        return $this->maxLevel;
    }

    public function setMaxLevel(int $maxLevel): void
    {
        $this->maxLevel = $maxLevel;
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
        if ($this->maxLevel < 1 || $this->maxLevel > PropertyPlacementLevelPrice::MAX_LEVEL) {
            throw new DomainException(sprintf('Максимальный VIP-уровень должен быть от 1 до %d', PropertyPlacementLevelPrice::MAX_LEVEL));
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
