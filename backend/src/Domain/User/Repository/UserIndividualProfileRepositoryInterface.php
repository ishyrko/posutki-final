<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\UserIndividualProfile;

interface UserIndividualProfileRepositoryInterface
{
    public function findByUserId(Id $userId): ?UserIndividualProfile;

    public function save(UserIndividualProfile $profile): void;

    public function deleteByUserId(Id $userId): void;
}
