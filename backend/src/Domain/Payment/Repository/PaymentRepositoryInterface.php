<?php

declare(strict_types=1);

namespace App\Domain\Payment\Repository;

use App\Domain\Payment\Entity\Payment;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;

    public function findById(int $id): ?Payment;

    public function findByCheckoutToken(string $checkoutToken): ?Payment;

    public function findByTrackingId(string $trackingId): ?Payment;

    public function findLatestForPurchase(int $purchaseId): ?Payment;
}
