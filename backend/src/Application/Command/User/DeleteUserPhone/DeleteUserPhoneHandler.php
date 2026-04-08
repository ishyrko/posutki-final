<?php

declare(strict_types=1);

namespace App\Application\Command\User\DeleteUserPhone;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;

readonly class DeleteUserPhoneHandler
{
    public function __construct(
        private UserPhoneRepositoryInterface $userPhoneRepository,
    ) {
    }

    public function __invoke(DeleteUserPhoneCommand $command): void
    {
        $phoneId = Id::fromString($command->phoneId);
        $userPhone = $this->userPhoneRepository->findById($phoneId);

        if ($userPhone === null) {
            throw new DomainException('Телефон не найден');
        }

        if ($userPhone->getUserId()->getValue() !== $command->userId) {
            throw new DomainException('Доступ запрещён');
        }

        $this->userPhoneRepository->delete($userPhone);
    }
}
