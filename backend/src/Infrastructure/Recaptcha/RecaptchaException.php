<?php

declare(strict_types=1);

namespace App\Infrastructure\Recaptcha;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class RecaptchaException extends UnprocessableEntityHttpException
{
    public function __construct(string $message = 'Проверка безопасности не пройдена', ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $previous);
    }
}
