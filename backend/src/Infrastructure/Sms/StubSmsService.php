<?php

declare(strict_types=1);

namespace App\Infrastructure\Sms;

use App\Domain\User\Service\SmsServiceInterface;
use Psr\Log\LoggerInterface;

class StubSmsService implements SmsServiceInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function send(string $phone, string $code): void
    {
        $this->logger->info("SMS verification code for {$phone}: {$code}");
    }
}
