<?php

declare(strict_types=1);

namespace App\Application\Notification\Contact;

use App\Domain\ContactFeedback\Event\FeedbackSubmittedEvent;
use App\Domain\ContactFeedback\Repository\ContactFeedbackRepositoryInterface;
use App\Infrastructure\Mail\ContactMailer;

final readonly class FeedbackSubmittedHandler
{
    public function __construct(
        private ContactFeedbackRepositoryInterface $contactFeedbackRepository,
        private ContactMailer $contactMailer,
    ) {
    }

    public function __invoke(FeedbackSubmittedEvent $event): void
    {
        $feedback = $this->contactFeedbackRepository->findById($event->feedbackId);
        if ($feedback === null) {
            return;
        }

        $this->contactMailer->sendFeedbackSubmitted($feedback);
    }
}
