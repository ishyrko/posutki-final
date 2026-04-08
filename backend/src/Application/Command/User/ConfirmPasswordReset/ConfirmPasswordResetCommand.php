<?php

declare(strict_types=1);

namespace App\Application\Command\User\ConfirmPasswordReset;

readonly class ConfirmPasswordResetCommand
{
    public function __construct(
        public string $email,
        public string $token,
        public string $newPassword,
    ) {
    }
}
