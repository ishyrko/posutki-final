<?php

declare(strict_types=1);

namespace App\Application\Query\Property\GetOwnerCalendar;

final class GetOwnerCalendarQuery
{
    public function __construct(
        public readonly string $propertyId,
        public readonly string $userId,
        public readonly ?string $exportBaseUrl = null,
    ) {
    }
}
