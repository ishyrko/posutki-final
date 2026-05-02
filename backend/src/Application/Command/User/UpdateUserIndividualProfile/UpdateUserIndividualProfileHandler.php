<?php

declare(strict_types=1);

namespace App\Application\Command\User\UpdateUserIndividualProfile;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\Shared\ValueObject\Unp;
use App\Domain\User\Entity\UserIndividualProfile;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Repository\UserBusinessProfileRepositoryInterface;
use App\Domain\User\Repository\UserIndividualProfileRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;

final readonly class UpdateUserIndividualProfileHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserIndividualProfileRepositoryInterface $individualProfileRepository,
        private UserBusinessProfileRepositoryInterface $businessProfileRepository,
    ) {
    }

    public function __invoke(UpdateUserIndividualProfileCommand $command): void
    {
        $userId = Id::fromString($command->userId);
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new UserNotFoundException('Пользователь не найден');
        }

        $unpValue = Unp::fromString($command->unp)->getValue();
        $lastName = trim($command->lastName);
        $firstName = trim($command->firstName);
        $middleName = $this->normalizeMiddleName($command->middleName);
        if ($middleName !== null && mb_strlen($middleName) < 2) {
            throw new DomainException('Отчество не короче 2 символов');
        }

        $this->businessProfileRepository->deleteByUserId($userId);

        $existing = $this->individualProfileRepository->findByUserId($userId);
        if ($existing !== null) {
            $existing->update($lastName, $firstName, $middleName, $unpValue);
            $this->individualProfileRepository->save($existing);

            return;
        }

        $profile = new UserIndividualProfile($user, $lastName, $firstName, $middleName, $unpValue);
        $this->individualProfileRepository->save($profile);
    }

    private function normalizeMiddleName(?string $middleName): ?string
    {
        if ($middleName === null) {
            return null;
        }
        $t = trim($middleName);

        return $t === '' ? null : $t;
    }
}
