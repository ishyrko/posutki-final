<?php

declare(strict_types=1);

namespace App\Application\Command\User\UpdateUserProfile;

use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Exception\UserNotFoundException;

readonly class UpdateUserProfileHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPhoneRepositoryInterface $userPhoneRepository,
    ) {
    }

    public function __invoke(UpdateUserProfileCommand $command): void
    {
        $userId = Id::fromString($command->userId);
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new UserNotFoundException('Пользователь не найден');
        }

        $user->updateProfile(
            $command->firstName,
            $command->lastName,
            $command->phone,
            $command->avatar
        );

        if ($command->phone !== null) {
            $userPhone = $this->userPhoneRepository->findByUserIdAndPhone($userId, $command->phone);
            if ($userPhone !== null && $userPhone->isVerified()) {
                $user->markPhoneVerified();
            }
        }

        $this->userRepository->save($user);
    }
}