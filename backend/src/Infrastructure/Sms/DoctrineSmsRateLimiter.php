<?php

declare(strict_types=1);

namespace App\Infrastructure\Sms;

use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\SmsSendLog;
use App\Domain\User\Service\SmsRateLimiterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

final readonly class DoctrineSmsRateLimiter implements SmsRateLimiterInterface
{
    private const WINDOW = '-24 hours';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private int $phoneCooldownSeconds,
        private int $perIpDailyLimit,
        private int $perIpUniquePhonesDaily,
        private int $globalDailyLimit,
    ) {
    }

    public function assertCanSend(string $normalizedPhone, ?string $ip, ?Id $userId, string $channel): void
    {
        $this->assertChannel($channel);
        $ipKey = $this->normalizeIpKey($ip);
        $conn = $this->entityManager->getConnection();
        $now = new \DateTimeImmutable();
        $windowStart = $now->modify(self::WINDOW);
        $windowStartStr = $windowStart->format('Y-m-d H:i:s');

        $globalCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM sms_send_log WHERE created_at >= ?',
            [$windowStartStr]
        );
        if ($globalCount >= $this->globalDailyLimit) {
            throw new TooManyRequestsHttpException(
                null,
                'Превышен дневной лимит отправки SMS. Попробуйте завтра.'
            );
        }

        $lastSentAtStr = $conn->fetchOne(
            'SELECT MAX(created_at) FROM sms_send_log WHERE phone = ?',
            [$normalizedPhone]
        );
        if (\is_string($lastSentAtStr) && $lastSentAtStr !== '') {
            $lastSent = new \DateTimeImmutable($lastSentAtStr);
            $elapsed = $now->getTimestamp() - $lastSent->getTimestamp();
            if ($elapsed < $this->phoneCooldownSeconds) {
                $wait = $this->phoneCooldownSeconds - $elapsed;
                throw new TooManyRequestsHttpException(
                    $wait,
                    sprintf('Код уже отправлен. Запросите повторно через %d с.', $wait)
                );
            }
        }

        $ipSmsCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM sms_send_log WHERE ip = ? AND created_at >= ?',
            [$ipKey, $windowStartStr]
        );
        if ($ipSmsCount >= $this->perIpDailyLimit) {
            throw new TooManyRequestsHttpException(
                null,
                'Слишком много попыток с вашего IP. Попробуйте позже.'
            );
        }

        $distinctPhones = (int) $conn->fetchOne(
            'SELECT COUNT(DISTINCT phone) FROM sms_send_log WHERE ip = ? AND created_at >= ?',
            [$ipKey, $windowStartStr]
        );
        $phoneSeenForIp = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM sms_send_log WHERE ip = ? AND phone = ? AND created_at >= ?',
            [$ipKey, $normalizedPhone, $windowStartStr]
        ) > 0;

        if ($distinctPhones >= $this->perIpUniquePhonesDaily && !$phoneSeenForIp) {
            throw new TooManyRequestsHttpException(
                null,
                'Слишком много разных номеров с вашего IP. Попробуйте позже.'
            );
        }
    }

    public function recordSent(string $normalizedPhone, ?string $ip, ?Id $userId, string $channel): void
    {
        $this->assertChannel($channel);
        $ipKey = $this->normalizeIpKey($ip);
        $userIdInt = $userId !== null ? $userId->getValue() : null;

        $log = new SmsSendLog(
            phone: $normalizedPhone,
            ip: $ipKey,
            userId: $userIdInt,
            channel: $channel,
        );

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    private function normalizeIpKey(?string $ip): string
    {
        if ($ip === null || $ip === '') {
            return 'unknown';
        }

        return $ip;
    }

    /**
     * @param self::CHANNEL_* $channel
     */
    private function assertChannel(string $channel): void
    {
        if (!\in_array($channel, [SmsRateLimiterInterface::CHANNEL_LOGIN, SmsRateLimiterInterface::CHANNEL_CABINET], true)) {
            throw new \InvalidArgumentException('Invalid SMS channel: ' . $channel);
        }
    }
}
