<?php

declare(strict_types=1);

namespace App\Domain\ContactFeedback\Event;

final readonly class FeedbackSubmittedEvent
{
    public function __construct(
        public string $feedbackId,
    ) {
    }
}
