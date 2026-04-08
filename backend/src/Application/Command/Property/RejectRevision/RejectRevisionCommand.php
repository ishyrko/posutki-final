<?php

declare(strict_types=1);

namespace App\Application\Command\Property\RejectRevision;

final readonly class RejectRevisionCommand
{
    public function __construct(
        public string $propertyId,
        public string $revisionId,
        public ?string $moderationComment = null,
    ) {
    }
}
