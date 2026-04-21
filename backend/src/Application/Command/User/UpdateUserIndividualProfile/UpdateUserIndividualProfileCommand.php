<?php

declare(strict_types=1);

namespace App\Application\Command\User\UpdateUserIndividualProfile;

final readonly class UpdateUserIndividualProfileCommand
{
    public function __construct(
        public string $userId,
        public string $lastName,
        public string $firstName,
        public ?string $middleName,
        public string $unp,
    ) {
    }
}
