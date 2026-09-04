<?php

declare(strict_types=1);

namespace App\Application\Command\Property\PublishFreeSlot;

final class PublishFreeSlotCommand
{
    public function __construct(
        public readonly string $propertyId,
        public readonly string $userId,
    ) {
    }
}
