<?php

declare(strict_types=1);

namespace App\Application\Command\User\UpdateUserProfile;

readonly class UpdateUserProfileCommand
{
    public function __construct(
        public string $userId,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $avatar = null,
        public ?string $telegram = null,
        public ?bool $phoneHasViber = null,
        public ?bool $phoneHasWhatsapp = null,
    ) {
    }
}