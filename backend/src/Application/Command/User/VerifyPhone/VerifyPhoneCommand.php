<?php

declare(strict_types=1);

namespace App\Application\Command\User\VerifyPhone;

readonly class VerifyPhoneCommand
{
    public function __construct(
        public string $userId,
        public string $phone,
        public string $code,
    ) {
    }
}
