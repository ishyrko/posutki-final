<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final readonly class AuthTokenIssuer
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenService $refreshTokenService,
    ) {
    }

    /**
     * @return array{token: string, refreshToken: string}
     */
    public function issue(User $user): array
    {
        return [
            'token' => $this->jwtManager->create($user),
            'refreshToken' => $this->refreshTokenService->issue($user),
        ];
    }
}
