<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

use App\Domain\Property\Entity\PropertyGeocoderResult;

interface PropertyGeocoderResultRepositoryInterface
{
    public function findByPropertyId(int $propertyId): ?PropertyGeocoderResult;

    public function save(PropertyGeocoderResult $result): void;
}
