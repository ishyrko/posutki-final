<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserProfileRequest
{
    #[Assert\NotBlank(message: 'Укажите имя')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Имя не короче {{ limit }} символов', maxMessage: 'Имя не длиннее {{ limit }} символов')]
    public string $firstName;

    #[Assert\NotBlank(message: 'Укажите фамилию')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Фамилия не короче {{ limit }} символов', maxMessage: 'Фамилия не длиннее {{ limit }} символов')]
    public string $lastName;

    #[Assert\Length(max: 20)]
    public ?string $phone = null;

    #[Assert\Url(message: 'Неверный URL аватара')]
    public ?string $avatar = null;
}
