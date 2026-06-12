<?php

declare(strict_types=1);

namespace App\Domain\Property\Validation;

use App\Domain\Shared\Exception\DomainException;

final class PropertyVideoUrlValidator
{
    /** @var list<string> */
    private const ALLOWED_HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'm.youtube.com',
        'youtu.be',
        'tiktok.com',
        'www.tiktok.com',
        'vm.tiktok.com',
    ];

    public static function assertValid(?string $videoUrl): void
    {
        if ($videoUrl === null || trim($videoUrl) === '') {
            return;
        }

        $videoUrl = trim($videoUrl);

        if (strlen($videoUrl) > 500) {
            throw new DomainException('Ссылка на видео не должна превышать 500 символов');
        }

        if (!filter_var($videoUrl, FILTER_VALIDATE_URL)) {
            throw new DomainException('Укажите корректную ссылку на видео YouTube или TikTok');
        }

        $host = strtolower((string) parse_url($videoUrl, PHP_URL_HOST));
        if (!in_array($host, self::ALLOWED_HOSTS, true)) {
            throw new DomainException('Допустимы только ссылки на YouTube или TikTok');
        }
    }
}
