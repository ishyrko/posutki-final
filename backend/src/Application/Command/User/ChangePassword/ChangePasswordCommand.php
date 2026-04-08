<?php

declare(strict_types=1);

namespace App\Application\Command\User\ChangePassword;

readonly class ChangePasswordCommand
{
    public function __construct(
        public string $userId,
        public string $currentPassword,
        public string $newPassword,
    ) {
    }
}