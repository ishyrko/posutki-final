<?php

declare(strict_types=1);

namespace App\Application\Command\Property\DeleteProperty;

readonly class DeletePropertyCommand
{
    public function __construct(
        public string $propertyId,
        public string $userId,
    ) {
    }
}