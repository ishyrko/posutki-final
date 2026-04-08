<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

use App\Domain\Property\Enum\DealType;
use App\Domain\Shared\Exception\DomainException;

final class DailyRentDetailsValidator
{
    public static function assertValid(
        string $dealType,
        string $propertyType,
        ?int $maxDailyGuests,
        ?int $dailyBedCount,
        ?string $checkInTime,
        ?string $checkOutTime,
    ): void {
        if ($dealType === DealType::Daily->value) {
            if ($propertyType === 'room') {
                throw new DomainException('Посуточная аренда комнат недоступна');
            }
            if ($maxDailyGuests === null || $maxDailyGuests <= 0) {
                throw new DomainException('Укажите максимальное число гостей для посуточной аренды');
            }
            if ($dailyBedCount === null || $dailyBedCount <= 0) {
                throw new DomainException('Укажите количество спальных мест для посуточной аренды');
            }
        }

        if ($checkInTime !== null && !self::isValidTime($checkInTime)) {
            throw new DomainException('Время заезда укажите в формате ЧЧ:ММ');
        }
        if ($checkOutTime !== null && !self::isValidTime($checkOutTime)) {
            throw new DomainException('Время выезда укажите в формате ЧЧ:ММ');
        }
    }

    private static function isValidTime(string $value): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }
}
