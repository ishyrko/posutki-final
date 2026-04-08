<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\User\Entity\User;

final class UserDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $email,
        public readonly ?string $pendingEmail,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $fullName,
        public readonly ?string $phone,
        public readonly bool $isPhoneVerified,
        public readonly ?string $avatar,
        public readonly array $roles,
        public readonly bool $isVerified,
        public readonly string $createdAt,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId()->getValue(),
            email: $user->getEmail()?->getValue(),
            pendingEmail: $user->getPendingEmail()?->getValue(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            fullName: $user->getFullName(),
            phone: $user->getPhone(),
            isPhoneVerified: $user->isPhoneVerified(),
            avatar: self::normalizeAvatarPublicUrl($user->getAvatar()),
            roles: $user->getRoles(),
            isVerified: $user->isVerified(),
            createdAt: $user->getCreatedAt()->format('Y-m-d H:i:s'),
        );
    }

    /**
     * В БД путь к файлу часто хранится без ведущего «/» (EasyAdmin и др.).
     * Без нормализации клиент резолвит относительный URL от текущего маршрута, а не от корня сайта.
     */
    private static function normalizeAvatarPublicUrl(?string $avatar): ?string
    {
        if ($avatar === null) {
            return null;
        }
        $trimmed = trim($avatar);
        if ($trimmed === '') {
            return null;
        }
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }
        if (str_starts_with($trimmed, '//')) {
            return $trimmed;
        }
        if (str_starts_with($trimmed, '/')) {
            return $trimmed;
        }
        $path = preg_replace('#^uploads/#', '', $trimmed) ?? $trimmed;

        return '/uploads/'.$path;
    }
}