<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

use App\Domain\Property\Enum\DealType;
use App\Domain\Shared\Exception\DomainException;

final class DealConditionsValidator
{
    /**
     * @param array<int, mixed>|null $dealConditions
     */
    public static function assertValid(?array $dealConditions, string $dealType, ?string $propertyType = null): void
    {
        if ($dealType === DealType::Daily->value && ($dealConditions !== null && $dealConditions !== [])) {
            throw new DomainException('Для посуточной аренды условия сделки не указываются');
        }

        if ($dealConditions === null || $dealConditions === []) {
            return;
        }

        throw new DomainException('Условия сделки не поддерживаются');
    }

    /**
     * @return string[]
     */
    public static function allowedForDealType(string $dealType, ?string $propertyType = null): array
    {
        return [];
    }
}
