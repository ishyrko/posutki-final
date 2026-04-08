<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

class UnauthorizedException extends DomainException
{
    public function __construct(string $message = 'Unauthorized access')
    {
        parent::__construct($message);
    }
}