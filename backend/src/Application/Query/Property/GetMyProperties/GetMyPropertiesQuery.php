<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetMyProperties;

final class GetMyPropertiesQuery
{
    public function __construct(
        public string $userId,
        public int $page = 1,
        public int $limit = 20,
        public ?string $status = null,
        public ?string $q = null,
        public ?int $cityId = null,
        public string $sort = 'createdAt',
        public string $sortOrder = 'DESC',
    ) {
    }
}
