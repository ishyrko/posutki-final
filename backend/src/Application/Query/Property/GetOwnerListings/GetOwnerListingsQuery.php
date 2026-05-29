<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetOwnerListings;

final class GetOwnerListingsQuery
{
    public function __construct(
        public readonly string $propertyId,
        public readonly ?string $viewerUserId = null,
        public readonly int $limit = 10,
    ) {
    }
}
