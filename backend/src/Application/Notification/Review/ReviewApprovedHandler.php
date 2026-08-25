<?php

declare(strict_types=1);

namespace App\Application\Notification\Review;

use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Review\Event\ReviewApprovedEvent;
use App\Domain\Review\Repository\ReviewRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Mail\ReviewMailer;

final readonly class ReviewApprovedHandler
{
    public function __construct(
        private ReviewRepositoryInterface $reviewRepository,
        private PropertyRepositoryInterface $propertyRepository,
        private UserRepositoryInterface $userRepository,
        private ReviewMailer $mailer,
    ) {
    }

    public function __invoke(ReviewApprovedEvent $event): void
    {
        $review = $this->reviewRepository->findById(Id::fromString($event->reviewId));
        if ($review === null) {
            return;
        }

        $property = $review->getProperty();
        $owner = $this->userRepository->findById($property->getOwnerId());
        if ($owner === null) {
            return;
        }

        $this->mailer->sendApprovedToOwner($owner, $property, $review);
    }
}
