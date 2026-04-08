<?php

declare(strict_types=1);

namespace App\Domain\Property\Event;

final readonly class PropertyRejectedEvent
{
    public function __construct(
        public string $propertyId,
        public ?string $moderationComment,
    ) {
    }
}
