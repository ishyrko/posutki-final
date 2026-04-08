<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Property\ValueObject\Address;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

class AddressType extends JsonType
{
    public const NAME = 'address';

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Address) {
            return json_encode([
                'building' => $value->getBuilding(),
                'block' => $value->getBlock(),
            ]);
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Address
    {
        if ($value === null || $value instanceof Address) {
            return $value;
        }

        $data = json_decode($value, true);

        return Address::create(
            $data['building'] ?? $data['street'] ?? '',
            $data['block'] ?? null
        );
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
