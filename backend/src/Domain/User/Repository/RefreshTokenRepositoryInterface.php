<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\RefreshToken;

interface RefreshTokenRepositoryInterface
{
    public function save(RefreshToken $refreshToken): void;

    public function findByTokenHash(string $tokenHash): ?RefreshToken;

    public function delete(RefreshToken $refreshToken): void;

    public function deleteAllForUser(Id $userId): void;
}
