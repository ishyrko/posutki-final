<?php

declare(strict_types=1);

namespace App\Domain\Property\Repository;

interface PropertyDailyStatRepositoryInterface
{
    public function upsertView(int $propertyId, \DateTimeImmutable $date): void;

    public function upsertPhoneView(int $propertyId, \DateTimeImmutable $date): void;

    /**
     * @return array<int, array{date:string, views:int, phoneViews:int}>
     */
    public function findByPropertyAndPeriod(int $propertyId, int $days): array;
}
