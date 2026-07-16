<?php

declare(strict_types=1);

namespace App\Domain\Property\Event;

final readonly class PlacementPaymentSucceededEvent
{
    public function __construct(
        public int $purchaseId,
    ) {
    }
}
