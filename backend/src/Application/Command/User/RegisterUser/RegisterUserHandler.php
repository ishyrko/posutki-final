<?php

declare(strict_types=1);

namespace App\Application\Command\User\RegisterUser;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\User\SendEmailVerification\SendEmailVerificationCommand;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use App\Domain\Shared\Exception\DomainException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): int
    {
        $email = Email::fromString($command->email);

        // Check if email already exists
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser !== null) {
            throw new DomainException('Пользователь с таким email уже существует');
        }

        $hashedPassword = password_hash($command->password, PASSWORD_BCRYPT);
        
        // Create user with hashed password
        $user = new User(
            $email,
            $hashedPassword,
            $command->firstName,
            $command->lastName,
            $command->phone
        );

        // Persist
        $this->userRepository->save($user);

        $this->commandBus->dispatch(new SendEmailVerificationCommand($command->email));

        return $user->getId()->getValue();
    }
}