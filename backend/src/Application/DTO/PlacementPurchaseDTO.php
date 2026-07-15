<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Entity\PropertyPlacementSlot;
use App\Domain\Property\Enum\PlacementPurchaseStatus;
use App\Domain\Property\Enum\PlacementPurchaseType;

final class PlacementPurchaseDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly int $propertyId,
        public readonly string $type,
        public readonly string $typeLabel,
        public readonly ?int $slotId,
        public readonly ?string $slotLabel,
        public readonly int $durationMonths,
        public readonly int $priceByn,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly string $source,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $activatedAt,
        public readonly ?\DateTimeImmutable $expiresAt,
        public readonly ?\DateTimeImmutable $reservationExpiresAt,
        public readonly ?string $note,
        public readonly ?string $propertyTitle = null,
    ) {
    }

    public static function fromEntity(
        PropertyPlacementPurchase $purchase,
        ?PropertyPlacementSlot $slot = null,
        ?string $propertyTitle = null,
    ): self {
        return new self(
            id: (int) $purchase->getId(),
            propertyId: $purchase->getPropertyId(),
            type: $purchase->getType(),
            typeLabel: PlacementPurchaseType::tryFrom($purchase->getType())?->label() ?? $purchase->getType(),
            slotId: $purchase->getSlotId(),
            slotLabel: $slot?->getLabel(),
            durationMonths: $purchase->getDurationMonths(),
            priceByn: $purchase->getPriceByn(),
            status: $purchase->getStatus(),
            statusLabel: PlacementPurchaseStatus::tryFrom($purchase->getStatus())?->label() ?? $purchase->getStatus(),
            source: $purchase->getSource(),
            createdAt: $purchase->getCreatedAt(),
            activatedAt: $purchase->getActivatedAt(),
            expiresAt: $purchase->getExpiresAt(),
            reservationExpiresAt: $purchase->getReservationExpiresAt(),
            note: $purchase->getNote(),
            propertyTitle: $propertyTitle,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'propertyId' => $this->propertyId,
            'propertyTitle' => $this->propertyTitle,
            'type' => $this->type,
            'typeLabel' => $this->typeLabel,
            'slotId' => $this->slotId,
            'slotLabel' => $this->slotLabel,
            'durationMonths' => $this->durationMonths,
            'priceByn' => $this->priceByn,
            'status' => $this->status,
            'statusLabel' => $this->statusLabel,
            'source' => $this->source,
            'createdAt' => $this->createdAt->format('c'),
            'activatedAt' => $this->activatedAt?->format('c'),
            'expiresAt' => $this->expiresAt?->format('c'),
            'reservationExpiresAt' => $this->reservationExpiresAt?->format('c'),
            'note' => $this->note,
        ];
    }
}
