<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserBusinessProfileRequest
{
    #[Assert\NotBlank(message: 'Укажите наименование организации')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Наименование не короче {{ limit }} символов', maxMessage: 'Наименование не длиннее {{ limit }} символов')]
    public string $organizationName;

    #[Assert\NotBlank(message: 'Укажите имя контакта')]
    #[Assert\Length(min: 2, max: 200, minMessage: 'Имя контакта не короче {{ limit }} символов', maxMessage: 'Имя контакта не длиннее {{ limit }} символов')]
    public string $contactName;

    #[Assert\NotBlank(message: 'Укажите УНП')]
    #[Assert\Regex(pattern: '/^[0-9A-Za-z]{9}$/', message: 'УНП: 9 символов (цифры и латинские буквы A–Z)')]
    public string $unp;
}
