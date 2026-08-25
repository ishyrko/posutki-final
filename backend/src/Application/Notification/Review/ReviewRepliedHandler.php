<?php

declare(strict_types=1);

namespace App\Application\Notification\Review;

use App\Domain\Review\Event\ReviewRepliedEvent;
use App\Domain\Review\Repository\ReviewRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Mail\ReviewMailer;

final readonly class ReviewRepliedHandler
{
    public function __construct(
        private ReviewRepositoryInterface $reviewRepository,
        private ReviewMailer $mailer,
    ) {
    }

    public function __invoke(ReviewRepliedEvent $event): void
    {
        $review = $this->reviewRepository->findById(Id::fromString($event->reviewId));
        if ($review === null || $review->getRating() < 4) {
            return;
        }

        $this->mailer->sendReplyToAuthor($review->getAuthor(), $review->getProperty(), $review);
    }
}
