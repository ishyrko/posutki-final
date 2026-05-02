<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\UserBusinessProfile;

interface UserBusinessProfileRepositoryInterface
{
    public function findByUserId(Id $userId): ?UserBusinessProfile;

    public function save(UserBusinessProfile $profile): void;

    public function deleteByUserId(Id $userId): void;
}
