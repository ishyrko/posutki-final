<?php

declare(strict_types=1);

namespace App\Application\Command\User\VerifyEmail;

final readonly class VerifyEmailCommand
{
    public function __construct(
        public string $email,
        public string $token,
    ) {
    }
}
