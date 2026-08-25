<?php

declare(strict_types=1);

namespace App\Domain\Review\Event;

final readonly class ReviewRepliedEvent
{
    public function __construct(
        public string $reviewId,
    ) {
    }
}
