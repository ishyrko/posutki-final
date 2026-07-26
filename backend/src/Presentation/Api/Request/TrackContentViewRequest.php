<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

class TrackContentViewRequest
{
    #[Assert\Length(max: 64)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/', message: 'Некорректный visitorId')]
    public ?string $visitorId = null;
}
