<?php

declare(strict_types=1);

namespace App\Application\Command\BookingInquiry\MarkRead;

final class MarkBookingInquiriesReadCommand
{
    public function __construct(
        public readonly string $ownerId,
    ) {
    }
}
