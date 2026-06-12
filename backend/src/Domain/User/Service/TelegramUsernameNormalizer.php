<?php

declare(strict_types=1);

namespace App\Domain\User\Service;

final class TelegramUsernameNormalizer
{
    private const MIN_LENGTH = 5;

    private const MAX_LENGTH = 32;

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $username = self::extractUsername($trimmed);
        self::assertValid($username);

        return $username;
    }

    private static function extractUsername(string $value): string
    {
        if (preg_match('#^(?:https?://)?(?:www\.)?(?:t\.me|telegram\.me)/([a-zA-Z0-9_]+)#i', $value, $matches) === 1) {
            return $matches[1];
        }

        if (str_starts_with($value, '@')) {
            return ltrim($value, '@');
        }

        return $value;
    }

    private static function assertValid(string $username): void
    {
        $length = strlen($username);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException('Ник Telegram: от 5 до 32 символов');
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $username)) {
            throw new \InvalidArgumentException('Ник Telegram: только латиница, цифры и подчёркивание, начинается с буквы');
        }

        if (str_ends_with($username, '_')) {
            throw new \InvalidArgumentException('Ник Telegram не может заканчиваться на подчёркивание');
        }
    }
}
