<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Property\Event\PlacementPaymentSucceededEvent;
use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class PlacementPaymentCompletionService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyPlacementService $placementService,
        private readonly MessageBusInterface $notificationBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function completeSuccessfulPayment(Payment $payment, ?string $transactionUid): void
    {
        $payment->markSuccessful(transactionUid: $transactionUid);

        $purchase = $this->purchaseRepository->findById($payment->getPurchaseId());
        if ($purchase === null) {
            $payment->setNote('Заявка на размещение не найдена — требуется ручная проверка');
            $this->paymentRepository->save($payment);

            return;
        }

        if (!$purchase->isPendingPayment()) {
            $payment->setNote('Оплата получена, но заявка уже не ожидает оплаты — требуется ручная проверка');
            $this->paymentRepository->save($payment);

            return;
        }

        $property = $this->propertyRepository->findById(Id::fromInt($purchase->getPropertyId()));
        if ($property === null) {
            $payment->setNote('Объявление не найдено — требуется ручная проверка');
            $this->paymentRepository->save($payment);

            return;
        }

        try {
            $this->placementService->activatePurchase($purchase, $property, adminId: null);
            $this->paymentRepository->save($payment);

            $purchaseId = $purchase->getId();
            if ($purchaseId !== null) {
                $this->notificationBus->dispatch(new PlacementPaymentSucceededEvent($purchaseId));
            }
        } catch (\Throwable $e) {
            $payment->setNote('Оплата получена, активация не удалась: ' . $e->getMessage());
            $this->paymentRepository->save($payment);
            $this->logger->error('Placement payment: failed to activate purchase', [
                'paymentId' => $payment->getId(),
                'purchaseId' => $purchase->getId(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
