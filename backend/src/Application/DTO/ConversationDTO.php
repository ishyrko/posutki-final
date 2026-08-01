<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\BookingInquiry\Entity\BookingInquiry;
use App\Domain\Message\Entity\Conversation;

final class ConversationDTO implements \JsonSerializable
{
    /**
     * @param array<string, mixed>|null $bookingInquiry
     */
    public function __construct(
        public readonly int $id,
        public readonly int $propertyId,
        public readonly ?string $propertyTitle,
        public readonly ?string $propertyImage,
        public readonly ?string $propertyType,
        public readonly ?string $propertyCitySlug,
        public readonly ?string $propertyRegionName,
        public readonly ?int $propertyPriceAmount,
        public readonly ?string $propertyPriceCurrency,
        public readonly ?string $propertyAddress,
        public readonly bool $propertyAvailable,
        public readonly bool $propertyLinkAvailable,
        public readonly int $sellerId,
        public readonly ?string $sellerName,
        public readonly int $buyerId,
        public readonly ?string $buyerName,
        public readonly ?string $lastMessageText,
        public readonly ?\DateTimeImmutable $lastMessageAt,
        public readonly int $unread,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?array $bookingInquiry = null,
    ) {
    }

    public static function fromEntity(
        Conversation $conversation,
        string $currentUserId,
        ?string $propertyTitle = null,
        ?string $propertyImage = null,
        ?string $propertyType = null,
        ?string $propertyCitySlug = null,
        ?string $propertyRegionName = null,
        ?int $propertyPriceAmount = null,
        ?string $propertyPriceCurrency = null,
        ?string $propertyAddress = null,
        bool $propertyAvailable = false,
        bool $propertyLinkAvailable = false,
        ?string $sellerName = null,
        ?string $buyerName = null,
        ?BookingInquiry $bookingInquiry = null,
    ): self {
        return new self(
            id: $conversation->getId()->getValue(),
            propertyId: (int) $conversation->getPropertyId(),
            propertyTitle: $propertyTitle,
            propertyImage: $propertyImage,
            propertyType: $propertyType,
            propertyCitySlug: $propertyCitySlug,
            propertyRegionName: $propertyRegionName,
            propertyPriceAmount: $propertyPriceAmount,
            propertyPriceCurrency: $propertyPriceCurrency,
            propertyAddress: $propertyAddress,
            propertyAvailable: $propertyAvailable,
            propertyLinkAvailable: $propertyLinkAvailable,
            sellerId: (int) $conversation->getSellerId(),
            sellerName: $sellerName,
            buyerId: (int) $conversation->getBuyerId(),
            buyerName: $buyerName,
            lastMessageText: $conversation->getLastMessageText(),
            lastMessageAt: $conversation->getLastMessageAt(),
            unread: $conversation->getUnreadFor($currentUserId),
            createdAt: $conversation->getCreatedAt(),
            bookingInquiry: $bookingInquiry !== null ? self::serializeBookingInquiry($bookingInquiry) : null,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'propertyId' => $this->propertyId,
            'propertyTitle' => $this->propertyTitle,
            'propertyImage' => $this->propertyImage,
            'propertyType' => $this->propertyType,
            'propertyCitySlug' => $this->propertyCitySlug,
            'propertyRegionName' => $this->propertyRegionName,
            'propertyPriceAmount' => $this->propertyPriceAmount,
            'propertyPriceCurrency' => $this->propertyPriceCurrency,
            'propertyAddress' => $this->propertyAddress,
            'propertyAvailable' => $this->propertyAvailable,
            'propertyLinkAvailable' => $this->propertyLinkAvailable,
            'sellerId' => $this->sellerId,
            'sellerName' => $this->sellerName,
            'buyerId' => $this->buyerId,
            'buyerName' => $this->buyerName,
            'lastMessageText' => $this->lastMessageText,
            'lastMessageAt' => $this->lastMessageAt?->format('c'),
            'unread' => $this->unread,
            'createdAt' => $this->createdAt->format('c'),
            'bookingInquiry' => $this->bookingInquiry,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function serializeBookingInquiry(BookingInquiry $inquiry): array
    {
        return [
            'id' => (string) $inquiry->getId()->getValue(),
            'status' => $inquiry->getStatus()->value,
            'checkIn' => $inquiry->getCheckIn()?->format('Y-m-d'),
            'checkOut' => $inquiry->getCheckOut()?->format('Y-m-d'),
            'guests' => $inquiry->getGuests(),
            'createdAt' => $inquiry->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
