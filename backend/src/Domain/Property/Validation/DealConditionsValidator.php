<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

use App\Domain\Shared\Exception\DomainException;

final class DealConditionsValidator
{
    private const ALLOWED = [
        'contactless_checkin',
        '24h_checkin',
        'pets_allowed',
        'parties_allowed',
        'accounting_docs',
        'no_smoking',
        'children_allowed',
    ];

    /**
     * @param array<int, mixed>|null $dealConditions
     */
    public static function assertValid(?array $dealConditions, string $dealType, ?string $propertyType = null): void
    {
        if ($dealConditions === null || $dealConditions === []) {
            return;
        }

        $unknown = array_diff($dealConditions, self::ALLOWED);
        if ($unknown !== []) {
            throw new DomainException(
                'Неизвестные условия размещения: ' . implode(', ', $unknown)
            );
        }
    }

    /**
     * @return string[]
     */
    public static function allowedForDealType(string $dealType, ?string $propertyType = null): array
    {
        return self::ALLOWED;
    }
}
