<?php

declare(strict_types=1);

namespace App\Application\Command\Property\ArchiveProperty;

final class ArchivePropertyCommand
{
    public function __construct(
        public readonly string $propertyId,
        public readonly string $userId,
    ) {
    }
}
