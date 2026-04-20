<?php

declare(strict_types=1);

namespace App\Application\Command\User\RequestPhoneVerification;

readonly class RequestPhoneVerificationCommand
{
    public function __construct(
        public string $userId,
        public string $phone,
        public ?string $ip = null,
    ) {
    }
}
