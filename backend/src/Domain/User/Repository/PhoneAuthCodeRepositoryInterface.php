<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\PhoneAuthCode;

interface PhoneAuthCodeRepositoryInterface
{
    public function save(PhoneAuthCode $phoneAuthCode): void;

    public function findByPhone(string $phone): ?PhoneAuthCode;

    public function delete(PhoneAuthCode $phoneAuthCode): void;
}
