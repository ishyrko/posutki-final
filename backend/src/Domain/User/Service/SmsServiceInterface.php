<?php

declare(strict_types=1);

namespace App\Domain\User\Service;

interface SmsServiceInterface
{
    public function send(string $phone, string $code): void;
}
