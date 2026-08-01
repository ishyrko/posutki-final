<?php

declare(strict_types=1);

namespace App\Domain\BookingInquiry\ValueObject;

enum BookingInquiryStatus: string
{
    case New = 'new';
    case Replied = 'replied';
    case Accepted = 'accepted';
    case Declined = 'declined';
}
