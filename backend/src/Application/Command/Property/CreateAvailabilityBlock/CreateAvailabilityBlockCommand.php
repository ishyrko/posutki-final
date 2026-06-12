<?php

declare(strict_types=1);

namespace App\Application\Command\Property\CreateAvailabilityBlock;

final class CreateAvailabilityBlockCommand
{
    public function __construct(
        public readonly string $propertyId,
        public readonly string $userId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?string $note = null,
    ) {
    }
}
