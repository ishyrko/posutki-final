<?php

declare(strict_types=1);

namespace App\Domain\Review\Event;

final readonly class ReviewApprovedEvent
{
    public function __construct(
        public string $reviewId,
    ) {
    }
}
