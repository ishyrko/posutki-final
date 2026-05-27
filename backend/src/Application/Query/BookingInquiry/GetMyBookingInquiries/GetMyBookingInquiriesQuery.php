<?php

declare(strict_types=1);

namespace App\Application\Query\BookingInquiry\GetMyBookingInquiries;

final class GetMyBookingInquiriesQuery
{
    public function __construct(
        public readonly string $ownerId,
        public readonly int $page = 1,
        public readonly int $limit = 20,
    ) {
    }
}
