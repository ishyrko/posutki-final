<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

class RequestSmsCodeRequest
{
    /** reCAPTCHA v2 response token (required when RECAPTCHA_ENABLED=true on server). */
    public string $recaptchaToken = '';

    #[Assert\NotBlank(message: 'Укажите телефон')]
    #[Assert\Regex(
        pattern: '/^\+?[1-9]\d{1,14}$/',
        message: 'Неверный формат номера телефона'
    )]
    public string $phone;
}
