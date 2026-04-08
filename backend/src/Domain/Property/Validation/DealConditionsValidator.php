<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

use App\Domain\Property\Enum\DealType;
use App\Domain\Shared\Exception\DomainException;

final class DealConditionsValidator
{
    /** @var string[] */
    private const SALE_OPTIONS = [
        'Чистая продажа',
        'Подбираются варианты',
        'Обмен',
    ];

    /** @var string[] */
    private const RENT_OPTIONS = [
        'Чистая аренда',
        'Подбираются варианты',
        'Предоплата 1 мес.',
        'Предоплата 3 мес.',
    ];

    /**
     * @param array<int, mixed>|null $dealConditions
     */
    public static function assertValid(?array $dealConditions, string $dealType, ?string $propertyType = null): void
    {
        if ($dealConditions === null) {
            return;
        }

        if (count($dealConditions) > 1) {
            throw new DomainException('Допустимо только одно условие сделки');
        }

        if ($dealConditions === []) {
            return;
        }

        $condition = $dealConditions[0] ?? null;
        if (!is_string($condition) || trim($condition) === '') {
            throw new DomainException('Условие сделки не должно быть пустым');
        }

        $allowedConditions = self::allowedForDealType($dealType, $propertyType);
        if (!in_array($condition, $allowedConditions, true)) {
            throw new DomainException('Недопустимое условие сделки для выбранного типа');
        }
    }

    /**
     * @return string[]
     */
    public static function allowedForDealType(string $dealType, ?string $propertyType = null): array
    {
        $allowed = match ($dealType) {
            DealType::Sale->value => self::SALE_OPTIONS,
            DealType::Rent->value => self::RENT_OPTIONS,
            default => [],
        };

        if ($propertyType !== null && in_array($propertyType, ['garage', 'parking'], true)) {
            $allowed = array_values(array_filter(
                $allowed,
                static fn (string $c): bool => $c !== 'Подбираются варианты',
            ));
        }

        return $allowed;
    }
}
