<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Property\Entity\PropertyPlacementLevelPrice;
use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Enum\PlacementPurchaseKind;
use App\Domain\Property\Enum\PlacementPurchaseStatus;

final class PlacementPurchaseDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly int $propertyId,
        public readonly string $kind,
        public readonly string $kindLabel,
        public readonly ?int $level,
        public readonly ?int $levelPriceId,
        public readonly ?int $durationMonths,
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
        public readonly ?int $basePurchaseId = null,
    ) {
    }

    public static function fromEntity(
        PropertyPlacementPurchase $purchase,
        ?PropertyPlacementLevelPrice $levelPrice = null,
        ?string $propertyTitle = null,
    ): self {
        return new self(
            id: (int) $purchase->getId(),
            propertyId: $purchase->getPropertyId(),
            kind: $purchase->getKind(),
            kindLabel: PlacementPurchaseKind::tryFrom($purchase->getKind())?->label() ?? $purchase->getKind(),
            level: $purchase->getLevel() ?? $levelPrice?->getLevel(),
            levelPriceId: $purchase->getLevelPriceId(),
            durationMonths: $purchase->getDurationMonths(),
            priceByn: $purchase->getPriceByn(),
            status: $purchase->getStatus(),
            statusLabel: self::resolveStatusLabel($purchase->getStatus()),
            source: $purchase->getSource(),
            createdAt: $purchase->getCreatedAt(),
            activatedAt: $purchase->getActivatedAt(),
            expiresAt: $purchase->getExpiresAt(),
            reservationExpiresAt: $purchase->getReservationExpiresAt(),
            note: $purchase->getNote(),
            propertyTitle: $propertyTitle,
            basePurchaseId: $purchase->getBasePurchaseId(),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'propertyId' => $this->propertyId,
            'propertyTitle' => $this->propertyTitle,
            'kind' => $this->kind,
            'kindLabel' => $this->kindLabel,
            'level' => $this->level,
            'levelPriceId' => $this->levelPriceId,
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
            'basePurchaseId' => $this->basePurchaseId,
        ];
    }

    private static function resolveStatusLabel(string $status): string
    {
        if ($status === PlacementPurchaseStatus::Superseded->value) {
            return PlacementPurchaseStatus::Active->label();
        }

        return PlacementPurchaseStatus::tryFrom($status)?->label() ?? $status;
    }
}
