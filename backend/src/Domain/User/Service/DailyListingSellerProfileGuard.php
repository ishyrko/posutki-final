<?php

declare(strict_types=1);

namespace App\Domain\User\Service;

final class DailyListingSellerProfileGuard implements DailyListingSellerProfileGuardInterface
{
    public function assertEligible(string $ownerId, string $dealType, ?string $sellerType): void
    {
        // Реквизиты физлица / организации опциональны; проверка не выполняется.
    }
}
