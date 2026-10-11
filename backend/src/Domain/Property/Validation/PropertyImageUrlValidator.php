<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

/**
 * Only permanent photo URLs are allowed: server uploads (/uploads/...) or absolute http(s) URLs.
 * Temporary browser URLs (blob:, data:) and other schemes must never be stored — they break the listing gallery.
 */
final class PropertyImageUrlValidator
{
    public static function isValid(mixed $url): bool
    {
        if (!is_string($url)) {
            return false;
        }

        $url = trim($url);
        if ($url === '' || strlen($url) > 2000) {
            return false;
        }

        if (str_starts_with($url, '/uploads/')) {
            return !str_contains($url, '..') && !preg_match('/[\x00-\x1F\x7F\s]/', $url);
        }

        if (preg_match('#^https?://#i', $url) !== 1) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * @param array<mixed> $urls
     */
    public static function allValid(array $urls): bool
    {
        foreach ($urls as $url) {
            if (!self::isValid($url)) {
                return false;
            }
        }

        return true;
    }
}
