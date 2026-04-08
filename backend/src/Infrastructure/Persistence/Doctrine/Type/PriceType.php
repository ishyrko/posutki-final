<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Property\ValueObject\Price;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

class PriceType extends JsonType
{
    public const NAME = 'price';

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Price) {
            return json_encode([
                'amount' => $value->getAmount(),
                'currency' => $value->getCurrency(),
            ]);
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Price
    {
        if ($value === null || $value instanceof Price) {
            return $value;
        }

        $data = json_decode($value, true);

        return Price::fromAmount($data['amount'], $data['currency']);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}