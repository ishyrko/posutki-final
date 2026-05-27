<?php

declare(strict_types=1);

namespace App\Domain\BookingInquiry\Entity;

use App\Domain\Shared\ValueObject\Id;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'booking_inquiries')]
#[ORM\Index(columns: ['owner_id'])]
#[ORM\Index(columns: ['property_id'])]
#[ORM\Index(columns: ['created_at'])]
class BookingInquiry
{
    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue]
    private Id $id;

    #[ORM\Column(type: 'id', name: 'property_id')]
    private Id $propertyId;

    #[ORM\Column(type: 'id', name: 'owner_id')]
    private Id $ownerId;

    #[ORM\Column(type: 'id', name: 'user_id', nullable: true)]
    private ?Id $userId = null;

    #[ORM\Column(type: 'string', length: 120)]
    private string $name;

    #[ORM\Column(type: 'string', length: 32)]
    private string $phone;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $guests = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, name: 'check_in')]
    private ?\DateTimeImmutable $checkIn = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, name: 'check_out')]
    private ?\DateTimeImmutable $checkOut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'boolean', name: 'is_read')]
    private bool $isRead = false;

    public function __construct(
        Id $propertyId,
        Id $ownerId,
        string $name,
        string $phone,
        ?Id $userId = null,
        ?string $email = null,
        ?int $guests = null,
        ?\DateTimeImmutable $checkIn = null,
        ?\DateTimeImmutable $checkOut = null,
        ?string $notes = null,
    ) {
        $this->propertyId = $propertyId;
        $this->ownerId = $ownerId;
        $this->userId = $userId;
        $this->name = $name;
        $this->phone = $phone;
        $this->email = $email;
        $this->guests = $guests;
        $this->checkIn = $checkIn;
        $this->checkOut = $checkOut;
        $this->notes = $notes;
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

    public function getOwnerId(): Id
    {
        return $this->ownerId;
    }

    public function getUserId(): ?Id
    {
        return $this->userId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getGuests(): ?int
    {
        return $this->guests;
    }

    public function getCheckIn(): ?\DateTimeImmutable
    {
        return $this->checkIn;
    }

    public function getCheckOut(): ?\DateTimeImmutable
    {
        return $this->checkOut;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function markAsRead(): void
    {
        $this->isRead = true;
    }
}
