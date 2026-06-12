<?php

declare(strict_types=1);

namespace App\Application\Command\Property\DeleteAvailabilityBlock;

final class DeleteAvailabilityBlockCommand
{
    public function __construct(
        public readonly string $propertyId,
        public readonly string $userId,
        public readonly string $blockId,
    ) {
    }
}
