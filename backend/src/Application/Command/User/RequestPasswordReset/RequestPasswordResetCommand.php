<?php

declare(strict_types=1);

namespace App\Application\Command\User\RequestPasswordReset;

readonly class RequestPasswordResetCommand
{
    public function __construct(
        public string $email,
    ) {
    }
}
