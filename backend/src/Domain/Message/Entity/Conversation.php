<?php

declare(strict_types=1);

namespace App\Domain\Message\Entity;

use App\Domain\Shared\ValueObject\Id;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'conversations')]
#[ORM\Index(columns: ['property_id'])]
#[ORM\UniqueConstraint(columns: ['property_id', 'buyer_id'])]
class Conversation
{
    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue]
    private Id $id;

    #[ORM\Column(type: 'id', name: 'property_id')]
    private Id $propertyId;

    #[ORM\Column(type: 'id', name: 'seller_id')]
    private Id $sellerId;

    #[ORM\Column(type: 'id', name: 'buyer_id')]
    private Id $buyerId;

    #[ORM\Column(type: 'text', nullable: true, name: 'last_message_text')]
    private ?string $lastMessageText = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'last_message_at')]
    private ?\DateTimeImmutable $lastMessageAt = null;

    #[ORM\Column(type: 'integer', name: 'unread_seller')]
    private int $unreadSeller = 0;

    #[ORM\Column(type: 'integer', name: 'unread_buyer')]
    private int $unreadBuyer = 0;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Id $propertyId,
        Id $sellerId,
        Id $buyerId,
    ) {
        $this->propertyId = $propertyId;
        $this->sellerId = $sellerId;
        $this->buyerId = $buyerId;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getPropertyId(): string
    {
        return (string) $this->propertyId->getValue();
    }

    public function getSellerId(): string
    {
        return (string) $this->sellerId->getValue();
    }

    public function getBuyerId(): string
    {
        return (string) $this->buyerId->getValue();
    }

    public function getLastMessageText(): ?string
    {
        return $this->lastMessageText;
    }

    public function getLastMessageAt(): ?\DateTimeImmutable
    {
        return $this->lastMessageAt;
    }

    public function getUnreadSeller(): int
    {
        return $this->unreadSeller;
    }

    public function getUnreadBuyer(): int
    {
        return $this->unreadBuyer;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function addMessage(string $text, string $senderId): void
    {
        $this->lastMessageText = $text;
        $this->lastMessageAt = new \DateTimeImmutable();

        if ($senderId === (string) $this->buyerId->getValue()) {
            $this->unreadSeller++;
        } else {
            $this->unreadBuyer++;
        }
    }

    public function markReadBy(string $userId): void
    {
        if ($userId === (string) $this->sellerId->getValue()) {
            $this->unreadSeller = 0;
        } elseif ($userId === (string) $this->buyerId->getValue()) {
            $this->unreadBuyer = 0;
        }
    }

    public function isParticipant(string $userId): bool
    {
        return (string) $this->sellerId->getValue() === $userId || (string) $this->buyerId->getValue() === $userId;
    }

    public function getUnreadFor(string $userId): int
    {
        if ($userId === (string) $this->sellerId->getValue()) {
            return $this->unreadSeller;
        }
        if ($userId === (string) $this->buyerId->getValue()) {
            return $this->unreadBuyer;
        }
        return 0;
    }
}
