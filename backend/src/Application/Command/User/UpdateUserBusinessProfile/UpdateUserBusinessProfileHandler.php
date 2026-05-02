<?php

declare(strict_types=1);

namespace App\Application\Command\User\UpdateUserBusinessProfile;

use App\Domain\Shared\ValueObject\Id;
use App\Domain\Shared\ValueObject\Unp;
use App\Domain\User\Entity\UserBusinessProfile;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Repository\UserBusinessProfileRepositoryInterface;
use App\Domain\User\Repository\UserIndividualProfileRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;

final readonly class UpdateUserBusinessProfileHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserBusinessProfileRepositoryInterface $businessProfileRepository,
        private UserIndividualProfileRepositoryInterface $individualProfileRepository,
    ) {
    }

    public function __invoke(UpdateUserBusinessProfileCommand $command): void
    {
        $userId = Id::fromString($command->userId);
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new UserNotFoundException('Пользователь не найден');
        }

        $unpValue = Unp::fromString($command->unp)->getValue();
        $organizationName = trim($command->organizationName);
        $contactName = trim($command->contactName);

        $this->individualProfileRepository->deleteByUserId($userId);

        $existing = $this->businessProfileRepository->findByUserId($userId);
        if ($existing !== null) {
            $existing->update($organizationName, $contactName, $unpValue);
            $this->businessProfileRepository->save($existing);

            return;
        }

        $profile = new UserBusinessProfile($user, $organizationName, $contactName, $unpValue);
        $this->businessProfileRepository->save($profile);
    }
}
