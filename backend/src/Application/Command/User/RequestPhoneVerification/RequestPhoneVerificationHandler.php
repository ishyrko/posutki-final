<?php

declare(strict_types=1);

namespace App\Application\Command\User\RequestPhoneVerification;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\UserPhone;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;
use App\Domain\User\Service\PhoneNumberNormalizer;
use App\Domain\User\Service\SmsRateLimiterInterface;
use App\Domain\User\Service\SmsServiceInterface;

readonly class RequestPhoneVerificationHandler
{
    public function __construct(
        private UserPhoneRepositoryInterface $userPhoneRepository,
        private SmsServiceInterface $smsService,
        private SmsRateLimiterInterface $smsRateLimiter,
    ) {
    }

    public function __invoke(RequestPhoneVerificationCommand $command): void
    {
        $userId = Id::fromString($command->userId);
        $normalizedPhone = PhoneNumberNormalizer::normalize($command->phone);
        $this->smsRateLimiter->assertCanSend(
            $normalizedPhone,
            $command->ip,
            $userId,
            SmsRateLimiterInterface::CHANNEL_CABINET
        );
        $userPhone = $this->userPhoneRepository->findByUserIdAndPhone($userId, $normalizedPhone);

        if ($userPhone === null) {
            $userPhone = new UserPhone($userId, $normalizedPhone);
        }

        if ($userPhone->isVerified()) {
            throw new DomainException('Телефон уже подтверждён');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = new \DateTimeImmutable('+10 minutes');

        $userPhone->setVerificationCode($code, $expiresAt);
        $this->userPhoneRepository->save($userPhone);

        $this->smsService->send($normalizedPhone, $code);
        $this->smsRateLimiter->recordSent(
            $normalizedPhone,
            $command->ip,
            $userId,
            SmsRateLimiterInterface::CHANNEL_CABINET
        );
    }
}
