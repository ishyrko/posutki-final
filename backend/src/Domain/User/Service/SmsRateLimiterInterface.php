<?php

declare(strict_types=1);

namespace App\Domain\User\Service;

use App\Domain\Shared\ValueObject\Id;

interface SmsRateLimiterInterface
{
    public const CHANNEL_LOGIN = 'login';

    public const CHANNEL_CABINET = 'cabinet';

    /**
     * @param self::CHANNEL_* $channel
     */
    public function assertCanSend(string $normalizedPhone, ?string $ip, ?Id $userId, string $channel): void;

    /**
     * @param self::CHANNEL_* $channel
     */
    public function recordSent(string $normalizedPhone, ?string $ip, ?Id $userId, string $channel): void;
}
