<?php

declare(strict_types=1);

namespace App\Domain\BookingInquiry\Event;

final readonly class BookingInquirySubmittedEvent
{
    public function __construct(
        public string $inquiryId,
    ) {
    }
}
