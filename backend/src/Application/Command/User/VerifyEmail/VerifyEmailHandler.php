<?php

declare(strict_types=1);

namespace App\Application\Command\User\VerifyEmail;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;

final class VerifyEmailHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(VerifyEmailCommand $command): void
    {
        $user = $this->userRepository->findByEmail(Email::fromString($command->email));

        if ($user === null) {
            throw new DomainException('Пользователь не найден');
        }

        $user->confirmEmailVerification($command->token);
        $this->userRepository->save($user);
    }
}
