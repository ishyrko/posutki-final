<?php

declare(strict_types=1);

namespace App\Application\Command\User\VerifyPhone;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;

readonly class VerifyPhoneHandler
{
    public function __construct(
        private UserPhoneRepositoryInterface $userPhoneRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(VerifyPhoneCommand $command): void
    {
        $userId = Id::fromString($command->userId);
        $userPhone = $this->userPhoneRepository->findByUserIdAndPhone($userId, $command->phone);

        if ($userPhone === null) {
            throw new DomainException('Телефон не найден');
        }

        if (!$userPhone->verify($command->code)) {
            throw new DomainException('Неверный или просроченный код подтверждения');
        }

        $this->userPhoneRepository->save($userPhone);

        $user = $this->userRepository->findById($userId);
        if ($user !== null && $user->getPhone() === $command->phone) {
            $user->markPhoneVerified();
            $this->userRepository->save($user);
        }
    }
}
