<?php

declare(strict_types=1);

namespace App\Application\Command\Property\PublishProperty;

final class PublishPropertyCommand
{
    public function __construct(
        public readonly string $propertyId,
        public readonly string $userId, // For authorization check
    ) {
    }
}