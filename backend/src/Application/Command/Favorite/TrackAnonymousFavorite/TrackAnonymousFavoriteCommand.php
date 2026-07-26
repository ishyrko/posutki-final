<?php

declare(strict_types=1);

namespace App\Application\Command\Favorite\TrackAnonymousFavorite;

readonly class TrackAnonymousFavoriteCommand
{
    public function __construct(
        public string $visitorId,
        public string $propertyId,
    ) {
    }
}
