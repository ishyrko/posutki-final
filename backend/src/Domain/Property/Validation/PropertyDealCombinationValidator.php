<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

use App\Domain\Property\Enum\DealType;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Shared\Exception\DomainException;

final class PropertyDealCombinationValidator
{
    public static function assertValid(string $dealType, string $propertyType): void
    {
        if ($dealType === DealType::Rent->value && $propertyType === PropertyType::Land->value) {
            throw new DomainException('Аренда участков недоступна');
        }
        if ($dealType === DealType::Daily->value && $propertyType === PropertyType::Room->value) {
            throw new DomainException('Посуточная сдача комнат недоступна');
        }
    }
}
