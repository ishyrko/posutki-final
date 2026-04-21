<?php

declare(strict_types=1);

namespace App\Application\Command\User\UpdateUserBusinessProfile;

final readonly class UpdateUserBusinessProfileCommand
{
    public function __construct(
        public string $userId,
        public string $organizationName,
        public string $contactName,
        public string $unp,
    ) {
    }
}
