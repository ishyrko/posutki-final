<?php

declare(strict_types=1);

namespace App\Application\Command\BookingInquiry\MarkRead;

use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;

final class MarkBookingInquiriesReadHandler
{
    public function __construct(
        private readonly BookingInquiryRepositoryInterface $bookingInquiryRepository,
    ) {
    }

    public function __invoke(MarkBookingInquiriesReadCommand $command): void
    {
        $this->bookingInquiryRepository->markAllAsReadByOwnerId($command->ownerId);
    }
}
