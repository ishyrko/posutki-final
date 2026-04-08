<?php

declare(strict_types=1);

namespace App\Application\Query\User\GetUserProfile;

use App\Application\DTO\UserDTO;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\Shared\Exception\DomainException;

final class GetUserProfileHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(GetUserProfileQuery $query): UserDTO
    {
        $userId = Id::fromString($query->userId);
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new DomainException('Пользователь не найден');
        }

        return UserDTO::fromEntity($user);
    }
}