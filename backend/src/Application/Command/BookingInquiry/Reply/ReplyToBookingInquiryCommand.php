<?php

declare(strict_types=1);

namespace App\Application\Command\BookingInquiry\Reply;

final class ReplyToBookingInquiryCommand
{
    public function __construct(
        public readonly string $inquiryId,
        public readonly string $ownerId,
        public readonly string $text,
    ) {
    }
}
