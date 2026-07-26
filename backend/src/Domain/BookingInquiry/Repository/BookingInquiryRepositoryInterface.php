<?php

declare(strict_types=1);

namespace App\Domain\BookingInquiry\Repository;

use App\Domain\BookingInquiry\Entity\BookingInquiry;

interface BookingInquiryRepositoryInterface
{
    public function save(BookingInquiry $inquiry): void;

    public function findById(string $id): ?BookingInquiry;

    /**
     * @return BookingInquiry[]
     */
    public function findByOwnerId(string $ownerId, int $page, int $limit): array;

    public function countByOwnerId(string $ownerId): int;

    public function countUnreadByOwnerId(string $ownerId): int;

    public function markAllAsReadByOwnerId(string $ownerId): void;

    /**
     * @return array<int, array{date: string, count: int}>
     */
    public function findDailyCountsByProperty(int $propertyId, int $days): array;
}
