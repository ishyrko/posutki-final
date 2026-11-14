<?php

declare(strict_types=1);

namespace App\Domain\Property\Event;

final readonly class PropertyRevisionRejectedEvent
{
    public function __construct(
        public string $propertyId,
        public ?string $moderationComment,
    ) {
    }
}
