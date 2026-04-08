<?php

declare(strict_types=1);

namespace App\Application\Query\User\GetUserProfile;

final class GetUserProfileQuery
{
    public function __construct(
        public readonly string $userId,
    ) {
    }
}