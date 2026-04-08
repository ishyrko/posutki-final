<?php

declare(strict_types=1);

namespace App\Application\Command\Auth\VerifySmsCode;

readonly class VerifySmsCodeCommand
{
    public function __construct(
        public string $phone,
        public string $code,
    ) {
    }
}
