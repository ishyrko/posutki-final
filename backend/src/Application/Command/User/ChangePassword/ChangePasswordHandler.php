<?php

declare(strict_types=1);

namespace App\Application\Command\User\ChangePassword;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Exception\UserNotFoundException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class ChangePasswordHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(ChangePasswordCommand $command): void
    {
        $user = $this->userRepository->findById(Id::fromString($command->userId));

        if (!$user) {
            throw new UserNotFoundException('Пользователь не найден');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $command->currentPassword)) {
            throw new DomainException('Неверный текущий пароль');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $command->newPassword);
        
        $user->changePassword($hashedPassword);

        $this->userRepository->save($user);
    }
}