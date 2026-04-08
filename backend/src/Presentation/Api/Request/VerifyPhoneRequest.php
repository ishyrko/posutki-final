<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

class VerifyPhoneRequest
{
    #[Assert\NotBlank(message: 'Укажите телефон')]
    #[Assert\Regex(
        pattern: '/^\+?[1-9]\d{1,14}$/',
        message: 'Неверный формат номера телефона'
    )]
    public string $phone;

    #[Assert\NotBlank(message: 'Укажите код')]
    #[Assert\Regex(pattern: '/^\d{6}$/', message: 'Код должен состоять из 6 цифр')]
    public string $code;
}
