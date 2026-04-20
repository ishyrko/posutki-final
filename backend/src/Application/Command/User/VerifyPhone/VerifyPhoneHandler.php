<?php

declare(strict_types=1);

namespace App\Application\Command\User\VerifyPhone;

use App\Domain\Shared\Exception\ConflictException;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Service\PhoneNumberNormalizer;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

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
        $normalizedPhone = PhoneNumberNormalizer::normalize($command->phone);

        $verifiedOwner = $this->userRepository->findVerifiedByPhone($normalizedPhone);
        if ($verifiedOwner !== null && $verifiedOwner->getId()->getValue() !== $userId->getValue()) {
            throw new ConflictException('Этот телефон уже подтверждён другим пользователем');
        }

        $userPhone = $this->userPhoneRepository->findByUserIdAndPhone($userId, $normalizedPhone);

        if ($userPhone === null) {
            throw new DomainException('Телефон не найден');
        }

        if (!$userPhone->verify($command->code)) {
            throw new DomainException('Неверный или просроченный код подтверждения');
        }

        $this->userPhoneRepository->save($userPhone);

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            return;
        }

        $mainPhone = $user->getPhone();
        if ($mainPhone === null || trim($mainPhone) === '') {
            $user->setVerifiedProfilePhone($normalizedPhone);
            try {
                $this->userRepository->save($user);
            } catch (UniqueConstraintViolationException) {
                throw new ConflictException('Этот телефон уже подтверждён другим пользователем');
            }

            return;
        }

        $mainPhoneNormalized = PhoneNumberNormalizer::normalize($mainPhone);
        if ($mainPhoneNormalized === $normalizedPhone) {
            $user->markPhoneVerified();
            try {
                $this->userRepository->save($user);
            } catch (UniqueConstraintViolationException) {
                throw new ConflictException('Этот телефон уже подтверждён другим пользователем');
            }
        }
    }
}
