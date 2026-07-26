<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

class SyncVisitorFavoritesRequest
{
    #[Assert\NotBlank(message: 'Укажите visitorId')]
    #[Assert\Length(max: 64)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/', message: 'Некорректный visitorId')]
    public ?string $visitorId = null;

    /** @var list<int> */
    #[Assert\Type(type: 'array')]
    #[Assert\All([
        new Assert\Type(type: 'integer'),
        new Assert\Positive(),
    ])]
    public array $propertyIds = [];
}
