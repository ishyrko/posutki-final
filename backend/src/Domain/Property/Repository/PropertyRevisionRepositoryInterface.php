<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\PropertyRevision;

interface PropertyRevisionRepositoryInterface
{
    public function save(PropertyRevision $revision): void;

    public function findLatestByPropertyAndStatus(int $propertyId, string $status): ?PropertyRevision;

    public function findById(int $revisionId): ?PropertyRevision;
}
