<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordRequest
{
    #[Assert\NotBlank(message: 'Укажите текущий пароль')]
    public string $currentPassword;

    #[Assert\NotBlank(message: 'Укажите новый пароль')]
    #[Assert\Length(min: 8, minMessage: 'Новый пароль не короче {{ limit }} символов')]
    public string $newPassword;
}
