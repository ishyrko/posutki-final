<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Shared\ValueObject\Slug;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class SlugType extends StringType
{
    public const NAME = 'slug';

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Slug) {
            return $value->getValue();
        }

        return $value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Slug
    {
        if ($value === null || $value instanceof Slug) {
            return $value;
        }

        return Slug::fromString($value);
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