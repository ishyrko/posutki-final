<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

/**
 * Комнаты в сделке (доля в квартире) на сайте не используются.
 */
final class RoomDealDetailsValidator
{
    public static function assertValid(
        string $dealType,
        string $propertyType,
        ?int $roomsInDeal,
        ?float $roomsArea,
    ): void {
    }
}
