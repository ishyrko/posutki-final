<?php

declare(strict_types=1);

namespace App\Application\Query\User\GetUserProfile;

use App\Application\DTO\UserDTO;
use App\Domain\User\Repository\UserBusinessProfileRepositoryInterface;
use App\Domain\User\Repository\UserIndividualProfileRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\Shared\Exception\DomainException;

final class GetUserProfileHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserIndividualProfileRepositoryInterface $individualProfileRepository,
        private readonly UserBusinessProfileRepositoryInterface $businessProfileRepository,
    ) {
    }

    public function __invoke(GetUserProfileQuery $query): UserDTO
    {
        $userId = Id::fromString($query->userId);
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new DomainException('Пользователь не найден');
        }

        $individual = $this->individualProfileRepository->findByUserId($userId);
        $business = $this->businessProfileRepository->findByUserId($userId);

        return UserDTO::fromEntity($user, $individual, $business);
    }
}