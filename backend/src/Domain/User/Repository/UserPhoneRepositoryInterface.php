<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\UserPhone;
use App\Domain\Shared\ValueObject\Id;

interface UserPhoneRepositoryInterface
{
    public function save(UserPhone $userPhone): void;

    public function delete(UserPhone $userPhone): void;

    public function findById(Id $id): ?UserPhone;

    /** @return UserPhone[] */
    public function findByUserId(Id $userId): array;

    public function findByUserIdAndPhone(Id $userId, string $phone): ?UserPhone;

    /** @return UserPhone[] */
    public function findVerifiedByUserId(Id $userId): array;
}
