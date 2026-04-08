<?php

declare(strict_types=1);

namespace App\Application\Command\User\ConfirmEmailChange;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;

final class ConfirmEmailChangeHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(ConfirmEmailChangeCommand $command): void
    {
        $user = $this->userRepository->findByPendingEmail(Email::fromString($command->email));

        if ($user === null) {
            throw new DomainException('Пользователь не найден');
        }

        $user->confirmPendingEmail($command->token);
        $this->userRepository->save($user);
    }
}
