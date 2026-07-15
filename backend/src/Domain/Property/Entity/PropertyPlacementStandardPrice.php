<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'property_placement_standard_prices')]
#[ORM\UniqueConstraint(name: 'uniq_placement_standard_city', columns: ['city_id'])]
class PropertyPlacementStandardPrice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', name: 'city_id')]
    private int $cityId;

    #[ORM\Column(type: 'integer', name: 'price_byn_per_month')]
    private int $priceBynPerMonth;

    #[ORM\Column(type: 'boolean', name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct(int $cityId, int $priceBynPerMonth, bool $isActive = true)
    {
        $this->cityId = $cityId;
        $this->priceBynPerMonth = $priceBynPerMonth;
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

    public function getPriceBynPerMonth(): int
    {
        return $this->priceBynPerMonth;
    }

    public function setPriceBynPerMonth(int $priceBynPerMonth): void
    {
        $this->priceBynPerMonth = $priceBynPerMonth;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }
}
