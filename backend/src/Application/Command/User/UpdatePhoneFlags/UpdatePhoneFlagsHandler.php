<?php

declare(strict_types=1);

namespace App\Application\Command\User\UpdatePhoneFlags;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;

readonly class UpdatePhoneFlagsHandler
{
    public function __construct(
        private UserPhoneRepositoryInterface $userPhoneRepository,
    ) {
    }

    public function __invoke(UpdatePhoneFlagsCommand $command): void
    {
        $phoneId = Id::fromString($command->phoneId);
        $userPhone = $this->userPhoneRepository->findById($phoneId);

        if ($userPhone === null) {
            throw new DomainException('Телефон не найден');
        }

        if ($userPhone->getUserId()->getValue() !== (int) $command->userId) {
            throw new DomainException('Доступ запрещён');
        }

        $userPhone->setMessengerFlags($command->hasViber, $command->hasWhatsapp);
        $this->userPhoneRepository->save($userPhone);
    }
}
