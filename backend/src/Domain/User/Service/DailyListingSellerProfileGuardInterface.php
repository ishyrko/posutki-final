<?php

declare(strict_types=1);

namespace App\Domain\User\Service;

interface DailyListingSellerProfileGuardInterface
{
    /**
     * Ensures owner has filled the legal profile required for daily listings.
     *
     * @throws \App\Domain\Shared\Exception\DomainException
     */
    public function assertEligible(string $ownerId, string $dealType, ?string $sellerType): void;
}
