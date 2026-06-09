<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\User\Entity\RefreshToken;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\RefreshTokenRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;

final class RefreshTokenService
{
    private const PLAIN_TOKEN_BYTES = 32;

    public function __construct(
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly int $refreshTtlSeconds = 2_592_000,
    ) {
    }

    public function issue(User $user): string
    {
        $plain = $this->generatePlainToken();
        $entity = new RefreshToken(
            $user->getId(),
            $this->hash($plain),
            new \DateTimeImmutable(sprintf('+%d seconds', $this->refreshTtlSeconds)),
        );
        $this->refreshTokenRepository->save($entity);

        return $plain;
    }

    public function exchange(string $plainToken): User
    {
        $stored = $this->refreshTokenRepository->findByTokenHash($this->hash($plainToken));
        if ($stored === null || $stored->isExpired()) {
            if ($stored !== null) {
                $this->refreshTokenRepository->delete($stored);
            }

            throw new DomainException('Недействительный или просроченный refresh-токен');
        }

        $user = $this->userRepository->findById($stored->getUserId());
        if ($user === null) {
            $this->refreshTokenRepository->delete($stored);
            throw new DomainException('Пользователь не найден');
        }

        $this->refreshTokenRepository->delete($stored);

        return $user;
    }

    public function revokeAllForUser(User $user): void
    {
        $this->refreshTokenRepository->deleteAllForUser($user->getId());
    }

    private function generatePlainToken(): string
    {
        return bin2hex(random_bytes(self::PLAIN_TOKEN_BYTES));
    }

    private function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
