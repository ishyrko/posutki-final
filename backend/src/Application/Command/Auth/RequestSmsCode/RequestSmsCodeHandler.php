<?php

declare(strict_types=1);

namespace App\Application\Command\Auth\RequestSmsCode;

use App\Domain\User\Entity\PhoneAuthCode;
use App\Domain\User\Repository\PhoneAuthCodeRepositoryInterface;
use App\Domain\User\Service\SmsServiceInterface;

final readonly class RequestSmsCodeHandler
{
    public function __construct(
        private PhoneAuthCodeRepositoryInterface $phoneAuthCodeRepository,
        private SmsServiceInterface $smsService,
    ) {
    }

    public function __invoke(RequestSmsCodeCommand $command): void
    {
        $normalizedPhone = $this->normalizePhone($command->phone);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = new \DateTimeImmutable('+10 minutes');

        $phoneAuthCode = $this->phoneAuthCodeRepository->findByPhone($normalizedPhone);
        if ($phoneAuthCode === null) {
            $phoneAuthCode = new PhoneAuthCode($normalizedPhone, $code, $expiresAt);
        } else {
            $phoneAuthCode->refreshCode($code, $expiresAt);
        }

        $this->phoneAuthCodeRepository->save($phoneAuthCode);
        $this->smsService->send($normalizedPhone, $code);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            throw new \InvalidArgumentException('Неверный номер телефона');
        }

        return '+' . $digits;
    }
}
