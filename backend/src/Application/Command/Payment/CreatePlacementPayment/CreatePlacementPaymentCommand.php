<?php

declare(strict_types=1);

namespace App\Application\Command\Payment\CreatePlacementPayment;

final class CreatePlacementPaymentCommand
{
    public function __construct(
        public readonly int $purchaseId,
        public readonly string $userId,
        public readonly ?string $customerIp = null,
        public readonly ?string $customerEmail = null,
    ) {
    }
}
