<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserProfileRequest
{
    #[Assert\When(
        expression: 'this.name !== null',
        constraints: [
            new Assert\NotBlank(message: 'Укажите имя'),
            new Assert\Length(
                min: 2,
                max: 100,
                minMessage: 'Имя не короче {{ limit }} символов',
                maxMessage: 'Имя не длиннее {{ limit }} символов',
            ),
        ],
    )]
    public ?string $name = null;

    #[Assert\Length(max: 20)]
    public ?string $phone = null;

    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: '#^(https?://|//).+|^/uploads/.+#',
        message: 'Некорректный адрес аватара',
    )]
    public ?string $avatar = null;

    #[Assert\Length(max: 100)]
    public ?string $telegram = null;

    public ?bool $phoneHasViber = null;

    public ?bool $phoneHasWhatsapp = null;
}
