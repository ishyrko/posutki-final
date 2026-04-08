<?php

declare(strict_types=1);

namespace App\Application\Command\Property\BoostProperty;

final class BoostPropertyCommand
{
    public function __construct(
        public readonly string $propertyId,
        public readonly string $userId,
    ) {
    }
}
