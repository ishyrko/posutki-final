<?php

declare(strict_types=1);

namespace App\Application\Command\Auth\VerifySmsCode;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use App\Domain\User\Entity\UserPhone;
use App\Domain\User\Repository\PhoneAuthCodeRepositoryInterface;
use App\Domain\User\Repository\UserPhoneRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final readonly class VerifySmsCodeHandler
{
    public function __construct(
        private PhoneAuthCodeRepositoryInterface $phoneAuthCodeRepository,
        private UserRepositoryInterface $userRepository,
        private UserPhoneRepositoryInterface $userPhoneRepository,
        private JWTTokenManagerInterface $jwtManager,
    ) {
    }

    public function __invoke(VerifySmsCodeCommand $command): string
    {
        $normalizedPhone = $this->normalizePhone($command->phone);
        $phoneAuthCode = $this->phoneAuthCodeRepository->findByPhone($normalizedPhone);

        if ($phoneAuthCode === null || !$phoneAuthCode->verify($command->code)) {
            throw new DomainException('Неверный или просроченный код подтверждения');
        }

        $user = $this->userRepository->findVerifiedByPhone($normalizedPhone);
        if ($user === null) {
            $user = User::registerViaPhone(
                phone: $normalizedPhone,
            );
            $this->userRepository->save($user);
        } else {
            $user->markPhoneVerified();
            $this->userRepository->save($user);
        }

        $this->saveVerifiedPhone($user, $normalizedPhone, $command->code);
        $this->phoneAuthCodeRepository->delete($phoneAuthCode);

        return $this->jwtManager->create($user);
    }

    private function saveVerifiedPhone(User $user, string $phone, string $code): void
    {
        $userPhone = $this->userPhoneRepository->findByUserIdAndPhone($user->getId(), $phone);
        if ($userPhone === null) {
            $userPhone = new UserPhone($user->getId(), $phone);
        }

        $userPhone->setVerificationCode($code, new \DateTimeImmutable('+1 minute'));
        $userPhone->verify($code);
        $this->userPhoneRepository->save($userPhone);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            throw new DomainException('Неверный номер телефона');
        }

        return '+' . $digits;
    }
}
