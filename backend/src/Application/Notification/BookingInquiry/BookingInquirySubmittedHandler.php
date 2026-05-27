<?php

declare(strict_types=1);

namespace App\Application\Notification\BookingInquiry;

use App\Domain\BookingInquiry\Event\BookingInquirySubmittedEvent;
use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Mail\BookingInquiryMailer;

final readonly class BookingInquirySubmittedHandler
{
    public function __construct(
        private BookingInquiryRepositoryInterface $bookingInquiryRepository,
        private PropertyRepositoryInterface $propertyRepository,
        private UserRepositoryInterface $userRepository,
        private BookingInquiryMailer $mailer,
    ) {
    }

    public function __invoke(BookingInquirySubmittedEvent $event): void
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

        $this->mailer->sendToOwner($owner, $property, $inquiry);
    }
}
