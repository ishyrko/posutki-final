<?php

declare(strict_types=1);

namespace App\Application\Notification\BookingInquiry;

use App\Domain\BookingInquiry\Event\BookingInquiryRepliedEvent;
use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Mail\BookingInquiryMailer;

final readonly class BookingInquiryRepliedHandler
{
    public function __construct(
        private BookingInquiryRepositoryInterface $bookingInquiryRepository,
        private PropertyRepositoryInterface $propertyRepository,
        private UserRepositoryInterface $userRepository,
        private BookingInquiryMailer $mailer,
    ) {
    }

    public function __invoke(BookingInquiryRepliedEvent $event): void
    {
        $inquiry = $this->bookingInquiryRepository->findById($event->inquiryId);
        if ($inquiry === null) {
            return;
        }

        $property = $this->propertyRepository->findById($inquiry->getPropertyId());
        if ($property === null) {
            return;
        }

        $owner = $this->userRepository->findById($inquiry->getOwnerId());
        if ($owner === null) {
            return;
        }

        $this->mailer->sendReplyToGuest($owner, $property, $inquiry, $event->guestEmail);
    }
}
