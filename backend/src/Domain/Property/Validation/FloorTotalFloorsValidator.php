<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

use App\Domain\Shared\Exception\DomainException;

final class FloorTotalFloorsValidator
{
    public static function assertValid(?int $floor, ?int $totalFloors): void
    {
        if ($floor === null || $totalFloors === null) {
            return;
        }

        if ($floor > $totalFloors) {
            throw new DomainException('Этаж не может быть больше чем этажей в доме');
        }
    }
}
