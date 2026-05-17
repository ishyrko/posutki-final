<?php

declare(strict_types=1);

namespace App\Application\Command\User\UpdatePhoneFlags;

readonly class UpdatePhoneFlagsCommand
{
    public function __construct(
        public string $userId,
        public string $phoneId,
        public bool $hasViber,
        public bool $hasWhatsapp,
    ) {
    }
}
