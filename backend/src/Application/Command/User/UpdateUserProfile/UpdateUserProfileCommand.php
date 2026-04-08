<?php

declare(strict_types=1);

namespace App\Application\Command\User\UpdateUserProfile;

readonly class UpdateUserProfileCommand
{
    public function __construct(
        public string $userId,
        public string $firstName,
        public string $lastName,
        public ?string $phone,
        public ?string $avatar,
    ) {
    }
}