<?php

declare(strict_types=1);

namespace App\Application\Command\Property\ApproveRevision;

final readonly class ApproveRevisionCommand
{
    public function __construct(
        public string $propertyId,
        public string $revisionId,
    ) {
    }
}
