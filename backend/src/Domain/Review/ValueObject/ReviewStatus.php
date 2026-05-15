<?php

declare(strict_types=1);

namespace App\Domain\Review\ValueObject;

enum ReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
