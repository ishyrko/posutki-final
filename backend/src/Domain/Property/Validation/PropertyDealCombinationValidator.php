<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

use App\Domain\Property\Enum\DealType;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Shared\Exception\DomainException;

/**
 * Сайт только посуточная аренда квартир и домов.
 */
final class PropertyDealCombinationValidator
{
    public static function assertValid(string $dealType, string $propertyType): void
    {
        if ($dealType !== DealType::Daily->value) {
            throw new DomainException('Поддерживается только посуточная аренда.');
        }

        if (PropertyType::tryFrom($propertyType) === null) {
            throw new DomainException('Допустимы только квартира или дом.');
        }
    }
}
