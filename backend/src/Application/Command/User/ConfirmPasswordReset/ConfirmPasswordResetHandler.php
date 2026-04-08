<?php

declare(strict_types=1);

namespace App\Application\Command\User\ConfirmPasswordReset;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

readonly class ConfirmPasswordResetHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(ConfirmPasswordResetCommand $command): void
    {
        $user = $this->userRepository->findByEmail(Email::fromString($command->email));

        if (!$user || !$user->isResetPasswordTokenValid($command->token)) {
            throw new BadCredentialsException('Недействительная или просроченная ссылка для сброса пароля');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $command->newPassword);
        $user->changePassword($hashedPassword);

        $this->userRepository->save($user);
    }
}
