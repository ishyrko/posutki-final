<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use App\Domain\Shared\ValueObject\Id;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'property_availability_blocks')]
#[ORM\Index(columns: ['property_id'], name: 'idx_property_availability_blocks_property')]
#[ORM\Index(columns: ['start_date', 'end_date'], name: 'idx_property_availability_blocks_dates')]
class PropertyAvailabilityBlock
{
    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue]
    private Id $id;

    #[ORM\Column(type: 'id', name: 'property_id')]
    private Id $propertyId;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, name: 'start_date')]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, name: 'end_date')]
    private \DateTimeImmutable $endDate;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Id $propertyId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?string $note = null,
    ) {
        $this->propertyId = $propertyId;
        $this->startDate = $startDate->setTime(0, 0);
        $this->endDate = $endDate->setTime(0, 0);
        $this->note = $note !== null && trim($note) !== '' ? trim($note) : null;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getPropertyId(): Id
    {
        return $this->propertyId;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
