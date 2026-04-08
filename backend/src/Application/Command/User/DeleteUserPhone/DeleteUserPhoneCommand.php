<?php

declare(strict_types=1);

namespace App\Application\Command\User\DeleteUserPhone;

readonly class DeleteUserPhoneCommand
{
    public function __construct(
        public string $userId,
        public string $phoneId,
    ) {
    }
}
