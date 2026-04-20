<?php

declare(strict_types=1);

namespace App\Application\Command\Auth\RequestSmsCode;

readonly class RequestSmsCodeCommand
{
    public function __construct(
        public string $phone,
        public ?string $ip = null,
    ) {
    }
}
