<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserIndividualProfileRequest
{
    #[Assert\NotBlank(message: 'Укажите фамилию')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Фамилия не короче {{ limit }} символов', maxMessage: 'Фамилия не длиннее {{ limit }} символов')]
    public string $lastName;

    #[Assert\NotBlank(message: 'Укажите имя')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Имя не короче {{ limit }} символов', maxMessage: 'Имя не длиннее {{ limit }} символов')]
    public string $firstName;

    #[Assert\Length(max: 100, maxMessage: 'Отчество не длиннее {{ limit }} символов')]
    public ?string $middleName = null;

    #[Assert\NotBlank(message: 'Укажите УНП')]
    #[Assert\Regex(pattern: '/^[0-9A-Za-z]{9}$/', message: 'УНП: 9 символов (цифры и латинские буквы A–Z)')]
    public string $unp;
}
