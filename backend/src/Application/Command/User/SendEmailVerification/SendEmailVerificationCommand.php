<?php

declare(strict_types=1);

namespace App\Application\Command\User\SendEmailVerification;

final readonly class SendEmailVerificationCommand
{
    public function __construct(
        public string $email,
    ) {
    }
}
