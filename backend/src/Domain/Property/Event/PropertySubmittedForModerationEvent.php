<?php

declare(strict_types=1);

namespace App\Domain\Property\Event;

final readonly class PropertySubmittedForModerationEvent
{
    public function __construct(
        public string $propertyId,
    ) {
    }
}
