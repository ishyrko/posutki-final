<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'property_placement_slots')]
#[ORM\Index(columns: ['city_id', 'is_active'], name: 'idx_placement_slots_city_active')]
class PropertyPlacementSlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', name: 'city_id')]
    private int $cityId;

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
        int $cityId,
        int $rankFrom,
        int $rankTo,
        int $capacity,
        int $priceBynPerMonth,
        bool $isTopSlot = false,
        int $sortOrder = 0,
        bool $isActive = true,
    ) {
        $this->cityId = $cityId;
        $this->rankFrom = $rankFrom;
        $this->rankTo = $rankTo;
        $this->capacity = $capacity;
        $this->priceBynPerMonth = $priceBynPerMonth;
        $this->isTopSlot = $isTopSlot;
        $this->sortOrder = $sortOrder;
        $this->isActive = $isActive;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCityId(): int
    {
        return $this->cityId;
    }

    public function setCityId(int $cityId): void
    {
        $this->cityId = $cityId;
    }

    public function getRankFrom(): int
    {
        return $this->rankFrom;
    }

    public function setRankFrom(int $rankFrom): void
    {
        $this->rankFrom = $rankFrom;
    }

    public function getRankTo(): int
    {
        return $this->rankTo;
    }

    public function setRankTo(int $rankTo): void
    {
        $this->rankTo = $rankTo;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): void
    {
        $this->capacity = $capacity;
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

    public function getLabel(): string
    {
        if ($this->rankFrom === $this->rankTo) {
            return (string) $this->rankFrom;
        }

        return $this->rankFrom . '-' . $this->rankTo;
    }
}
