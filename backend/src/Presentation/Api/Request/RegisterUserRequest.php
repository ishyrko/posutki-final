<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserRequest
{
    #[Assert\NotBlank(message: 'Укажите email')]
    #[Assert\Email(message: 'Неверный формат email')]
    public string $email;

    #[Assert\NotBlank(message: 'Укажите пароль')]
    #[Assert\Length(
        min: 8,
        max: 100,
        minMessage: 'Пароль не короче {{ limit }} символов'
    )]
    public string $password;

    #[Assert\NotBlank(message: 'Укажите имя')]
    #[Assert\Length(min: 2, max: 100)]
    public string $firstName;

    #[Assert\NotBlank(message: 'Укажите фамилию')]
    #[Assert\Length(min: 2, max: 100)]
    public string $lastName;

    #[Assert\Regex(
        pattern: '/^\+?[1-9]\d{1,14}$/',
        message: 'Неверный формат номера телефона'
    )]
    public ?string $phone = null;
}
