<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetMyProperties;

final class GetMyPropertiesSummaryQuery
{
    public function __construct(public string $userId)
    {
    }
}
