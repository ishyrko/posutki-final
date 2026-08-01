<?php

declare(strict_types=1);

namespace App\Application\Command\BookingInquiry\Accept;

final class AcceptBookingInquiryCommand
{
    public function __construct(
        public readonly string $inquiryId,
        public readonly string $ownerId,
    ) {
    }
}
