<?php

declare(strict_types=1);

namespace App\Application\Command\BookingInquiry\Submit;

final class SubmitBookingInquiryCommand
{
    public function __construct(
        public readonly string $propertyId,
        public readonly string $name,
        public readonly string $phone,
        public readonly ?string $email = null,
        public readonly ?int $guests = null,
        public readonly ?string $checkIn = null,
        public readonly ?string $checkOut = null,
        public readonly ?string $notes = null,
        public readonly ?string $userId = null,
    ) {
    }
}
