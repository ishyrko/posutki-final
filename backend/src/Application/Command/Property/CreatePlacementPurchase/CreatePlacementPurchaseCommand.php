<?php

declare(strict_types=1);

namespace App\Application\Command\Property\CreatePlacementPurchase;

final class CreatePlacementPurchaseCommand
{
    public function __construct(
        public readonly string $propertyId,
        public readonly string $userId,
        public readonly string $kind,
        public readonly ?int $level = null,
        public readonly ?int $durationMonths = null,
    ) {
    }
}
