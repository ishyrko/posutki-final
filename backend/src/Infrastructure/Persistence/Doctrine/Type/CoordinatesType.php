<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Property\ValueObject\Coordinates;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

class CoordinatesType extends JsonType
{
    public const NAME = 'coordinates';

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Coordinates) {
            return json_encode([
                'latitude' => $value->getLatitude(),
                'longitude' => $value->getLongitude(),
            ]);
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Coordinates
    {
        if ($value === null || $value instanceof Coordinates) {
            return $value;
        }

        $data = json_decode($value, true);

        return Coordinates::create($data['latitude'], $data['longitude']);
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