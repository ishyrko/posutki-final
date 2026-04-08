<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'property_daily_stats')]
#[ORM\UniqueConstraint(name: 'uq_property_date', columns: ['property_id', 'stat_date'])]
#[ORM\Index(columns: ['property_id', 'stat_date'], name: 'idx_property_daily_stats_property_date')]
class PropertyDailyStat
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'integer', name: 'property_id')]
    private int $propertyId;

    #[ORM\Column(type: 'date_immutable', name: 'stat_date')]
    private \DateTimeImmutable $statDate;

    #[ORM\Column(type: 'integer')]
    private int $views = 0;

    #[ORM\Column(type: 'integer', name: 'phone_views')]
    private int $phoneViews = 0;

    public function __construct(int $propertyId, \DateTimeImmutable $statDate)
    {
        $this->propertyId = $propertyId;
        $this->statDate = $statDate->setTime(0, 0);
    }

    public function incrementViews(): void
    {
        $this->views++;
    }

    public function incrementPhoneViews(): void
    {
        $this->phoneViews++;
    }

    public function getPropertyId(): int
    {
        return $this->propertyId;
    }

    public function getStatDate(): \DateTimeImmutable
    {
        return $this->statDate;
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function getPhoneViews(): int
    {
        return $this->phoneViews;
    }
}
