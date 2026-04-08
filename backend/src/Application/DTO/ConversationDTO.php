<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Message\Entity\Conversation;

final class ConversationDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly int $propertyId,
        public readonly ?string $propertyTitle,
        public readonly ?string $propertyImage,
        public readonly int $sellerId,
        public readonly ?string $sellerName,
        public readonly int $buyerId,
        public readonly ?string $buyerName,
        public readonly ?string $lastMessageText,
        public readonly ?\DateTimeImmutable $lastMessageAt,
        public readonly int $unread,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromEntity(
        Conversation $conversation,
        string $currentUserId,
        ?string $propertyTitle = null,
        ?string $propertyImage = null,
        ?string $sellerName = null,
        ?string $buyerName = null,
    ): self {
        return new self(
            id: $conversation->getId()->getValue(),
            propertyId: (int) $conversation->getPropertyId(),
            propertyTitle: $propertyTitle,
            propertyImage: $propertyImage,
            sellerId: (int) $conversation->getSellerId(),
            sellerName: $sellerName,
            buyerId: (int) $conversation->getBuyerId(),
            buyerName: $buyerName,
            lastMessageText: $conversation->getLastMessageText(),
            lastMessageAt: $conversation->getLastMessageAt(),
            unread: $conversation->getUnreadFor($currentUserId),
            createdAt: $conversation->getCreatedAt(),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'propertyId' => $this->propertyId,
            'propertyTitle' => $this->propertyTitle,
            'propertyImage' => $this->propertyImage,
            'sellerId' => $this->sellerId,
            'sellerName' => $this->sellerName,
            'buyerId' => $this->buyerId,
            'buyerName' => $this->buyerName,
            'lastMessageText' => $this->lastMessageText,
            'lastMessageAt' => $this->lastMessageAt?->format('c'),
            'unread' => $this->unread,
            'createdAt' => $this->createdAt->format('c'),
        ];
    }
}
