<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

use App\Domain\Property\Enum\DealType;
use App\Domain\Shared\Exception\DomainException;

final class RoomDealDetailsValidator
{
    public static function assertValid(
        string $dealType,
        string $propertyType,
        ?int $roomsInDeal,
        ?float $roomsArea,
    ): void {
        if ($propertyType !== 'room') {
            return;
        }

        if (!in_array($dealType, [DealType::Sale->value, DealType::Rent->value], true)) {
            return;
        }

        if ($roomsInDeal === null || $roomsInDeal <= 0) {
            throw new DomainException('Укажите количество комнат в сделке');
        }

        if ($roomsArea === null || $roomsArea <= 0) {
            throw new DomainException('Укажите площадь комнат в сделке');
        }
    }
}
