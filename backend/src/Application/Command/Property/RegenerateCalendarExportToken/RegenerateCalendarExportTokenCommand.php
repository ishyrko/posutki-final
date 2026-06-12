<?php

declare(strict_types=1);

namespace App\Application\Command\Property\RegenerateCalendarExportToken;

final class RegenerateCalendarExportTokenCommand
{
    public function __construct(
        public readonly string $propertyId,
        public readonly string $userId,
    ) {
    }
}
