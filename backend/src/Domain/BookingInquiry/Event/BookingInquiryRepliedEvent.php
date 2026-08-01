<?php

declare(strict_types=1);

namespace App\Domain\BookingInquiry\Event;

final readonly class BookingInquiryRepliedEvent
{
    public function __construct(
        public string $inquiryId,
        public string $guestEmail,
    ) {
    }
}
