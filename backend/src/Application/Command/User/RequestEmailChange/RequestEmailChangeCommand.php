<?php

declare(strict_types=1);

namespace App\Application\Command\User\RequestEmailChange;

final readonly class RequestEmailChangeCommand
{
    public function __construct(
        public string $userId,
        public string $email,
    ) {
    }
}
