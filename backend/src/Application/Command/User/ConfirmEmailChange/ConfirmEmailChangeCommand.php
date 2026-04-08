<?php

declare(strict_types=1);

namespace App\Application\Command\User\ConfirmEmailChange;

final readonly class ConfirmEmailChangeCommand
{
    public function __construct(
        public string $email,
        public string $token,
    ) {
    }
}
