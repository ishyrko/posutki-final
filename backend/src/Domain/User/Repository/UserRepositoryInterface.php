<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Email;
use App\Domain\Shared\ValueObject\Id;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(Id $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function findByPendingEmail(Email $email): ?User;

    public function findByPhone(string $phone): ?User;

    public function findVerifiedByPhone(string $phone): ?User;

    public function delete(User $user): void;
}