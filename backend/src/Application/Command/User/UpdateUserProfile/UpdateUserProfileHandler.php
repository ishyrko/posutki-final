<?php

declare(strict_types=1);

namespace App\Application\Command\User\UpdateUserProfile;

use App\Domain\Shared\Exception\ConflictException;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Service\PhoneNumberNormalizer;
use App\Domain\User\Service\TelegramUsernameNormalizer;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

readonly class UpdateUserProfileHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPhoneRepositoryInterface $userPhoneRepository,
    ) {
    }

    public function __invoke(UpdateUserProfileCommand $command): void
    {
        $userId = Id::fromString($command->userId);
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new UserNotFoundException('Пользователь не найден');
        }

        $normalizedPhone = $command->phone !== null
            ? $this->normalizeProfilePhone($command->phone)
            : null;

        if ($normalizedPhone !== null) {
            $currentNormalized = $this->normalizeStoredPhone($user->getPhone());
            if ($currentNormalized !== $normalizedPhone) {
                $verifiedOwner = $this->userRepository->findVerifiedByPhone($normalizedPhone);
                if ($verifiedOwner !== null && $verifiedOwner->getId()->getValue() !== $user->getId()->getValue()) {
                    throw new ConflictException('Этот телефон уже используется другим пользователем');
                }
            }
        }

        if ($command->name !== null || $command->phone !== null || $command->avatar !== null) {
            $firstName = $user->getFirstName();
            $lastName = $user->getLastName();
            if ($command->name !== null) {
                $firstName = trim($command->name);
                $lastName = '';
            }

            $user->updateProfile(
                $firstName,
                $lastName,
                $command->phone !== null ? $normalizedPhone : $user->getPhone(),
                $command->avatar,
            );
        }

        if ($command->telegram !== null || $command->phoneHasViber !== null || $command->phoneHasWhatsapp !== null) {
            $user->updateContactChannels(
                $this->normalizeTelegram($command->telegram),
                $command->phoneHasViber,
                $command->phoneHasWhatsapp,
            );
        }

        if ($command->phone !== null && $normalizedPhone !== null) {
            $userPhone = $this->userPhoneRepository->findByUserIdAndPhone($userId, $normalizedPhone);
            if ($userPhone !== null && $userPhone->isVerified()) {
                $user->markPhoneVerified();
            }
        }

        try {
            $this->userRepository->save($user);
        } catch (UniqueConstraintViolationException) {
            throw new ConflictException('Этот телефон уже используется другим пользователем');
        }
    }

    private function normalizeProfilePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        $trimmed = trim($phone);
        if ($trimmed === '') {
            return null;
        }
        try {
            return PhoneNumberNormalizer::normalize($trimmed);
        } catch (\InvalidArgumentException) {
            throw new DomainException('Неверный номер телефона');
        }
    }

    private function normalizeStoredPhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }
        try {
            return PhoneNumberNormalizer::normalize($phone);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function normalizeTelegram(?string $telegram): ?string
    {
        if ($telegram === null) {
            return null;
        }

        try {
            return TelegramUsernameNormalizer::normalize($telegram);
        } catch (\InvalidArgumentException $e) {
            throw new DomainException($e->getMessage());
        }
    }
}
