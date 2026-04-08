<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetProperty;

final class GetPropertyQuery
{
    public function __construct(
        public readonly string $propertyId,
        public readonly ?string $viewerUserId = null,
    ) {
    }
}