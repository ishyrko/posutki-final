<?php

declare(strict_types=1);

namespace App\Application\Query\User\GetUserPhones;

readonly class GetUserPhonesQuery
{
    public function __construct(
        public string $userId,
    ) {
    }
}
