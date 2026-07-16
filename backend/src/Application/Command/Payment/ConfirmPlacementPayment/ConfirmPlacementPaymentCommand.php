<?php

declare(strict_types=1);

namespace App\Application\Command\Payment\ConfirmPlacementPayment;

final class ConfirmPlacementPaymentCommand
{
    public function __construct(
        public readonly int $purchaseId,
        public readonly string $userId,
        public readonly string $checkoutToken,
    ) {
    }
}
