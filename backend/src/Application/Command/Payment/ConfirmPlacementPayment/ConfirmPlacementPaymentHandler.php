<?php

declare(strict_types=1);

namespace App\Application\Command\Payment\ConfirmPlacementPayment;

use App\Application\DTO\PlacementPurchaseDTO;
use App\Application\Service\PlacementPaymentCompletionService;
use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Enum\PaymentStatus;
use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementLevelPriceRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Payment\BePaid\BePaidCheckoutReader;
use App\Infrastructure\Payment\BePaid\BePaidGatewayClient;

final class ConfirmPlacementPaymentHandler
{
    public function __construct(
        private readonly PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyPlacementLevelPriceRepositoryInterface $levelPriceRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly BePaidGatewayClient $bePaidClient,
        private readonly BePaidCheckoutReader $checkoutReader,
        private readonly PlacementPaymentCompletionService $completionService,
    ) {
    }

    public function __invoke(ConfirmPlacementPaymentCommand $command): PlacementPurchaseDTO
    {
        $userId = Id::fromString($command->userId);
        $purchase = $this->purchaseRepository->findById($command->purchaseId);

        if ($purchase === null) {
            throw new DomainException('Заявка не найдена');
        }
        if (!$purchase->getOwnerId()->equals($userId)) {
            throw new DomainException('Нет прав на эту заявку');
        }

        $payment = $this->paymentRepository->findByCheckoutToken($command->checkoutToken);
        if ($payment === null || $payment->getPurchaseId() !== $command->purchaseId) {
            throw new DomainException('Платёж не найден');
        }

        if (!$purchase->isPendingPayment()) {
            return $this->toDto($purchase);
        }

        if ($payment->getStatus() === PaymentStatus::Successful->value) {
            $this->retryActivationIfNeeded($payment);

            $purchase = $this->purchaseRepository->findById($command->purchaseId);
            if ($purchase === null) {
                throw new DomainException('Заявка не найдена');
            }

            return $this->toDto($purchase);
        }

        if ($payment->isTerminal()) {
            throw new DomainException('Платёж уже завершён с другим статусом');
        }

        $checkoutStatus = $this->bePaidClient->getCheckoutStatus($command->checkoutToken);
        $payment->setRawPayload(json_encode($checkoutStatus, JSON_THROW_ON_ERROR));

        $bePaidStatus = $this->checkoutReader->readStatus($checkoutStatus);
        if ($bePaidStatus === 'successful') {
            if (!$this->verifyAmountAndTracking($payment, $checkoutStatus)) {
                $payment->markFailed('Несовпадение суммы или tracking_id в ответе bePaid');
                $this->paymentRepository->save($payment);

                throw new DomainException('Не удалось подтвердить платёж');
            }

            $this->completionService->completeSuccessfulPayment(
                $payment,
                $this->checkoutReader->readTransactionUid($checkoutStatus),
            );
        } elseif (in_array($bePaidStatus, ['failed', 'declined', 'fail'], true)) {
            $payment->markFailed('Платёж отклонён');
            $this->paymentRepository->save($payment);

            throw new DomainException('Платёж отклонён');
        } elseif (in_array($bePaidStatus, ['cancelled', 'canceled'], true)) {
            $payment->markCancelled('Платёж отменён');
            $this->paymentRepository->save($payment);

            throw new DomainException('Платёж отменён');
        } else {
            $this->paymentRepository->save($payment);

            throw new DomainException('Платёж ещё обрабатывается');
        }

        $purchase = $this->purchaseRepository->findById($command->purchaseId);
        if ($purchase === null) {
            throw new DomainException('Заявка не найдена');
        }

        return $this->toDto($purchase);
    }

    private function retryActivationIfNeeded(Payment $payment): void
    {
        $purchase = $this->purchaseRepository->findById($payment->getPurchaseId());
        if ($purchase === null || !$purchase->isPendingPayment()) {
            return;
        }

        $this->completionService->completeSuccessfulPayment($payment, $payment->getTransactionUid());
    }

    /**
     * @param array<string, mixed> $checkoutStatus
     */
    private function verifyAmountAndTracking(Payment $payment, array $checkoutStatus): bool
    {
        $order = $this->checkoutReader->readOrder($checkoutStatus);
        if ($order === null) {
            return false;
        }

        $trackingId = $order['tracking_id'] ?? null;
        if (is_string($trackingId) && $trackingId !== '' && $trackingId !== $payment->getTrackingId()) {
            return false;
        }

        $amountMinor = $order['amount'] ?? null;
        if ($amountMinor !== null && (int) $amountMinor !== $payment->getAmountByn() * 100) {
            return false;
        }

        $currency = $order['currency'] ?? null;
        if ($currency !== null && strtoupper((string) $currency) !== 'BYN') {
            return false;
        }

        return true;
    }

    private function toDto(\App\Domain\Property\Entity\PropertyPlacementPurchase $purchase): PlacementPurchaseDTO
    {
        $levelPrice = $purchase->getLevelPriceId() !== null
            ? $this->levelPriceRepository->findById($purchase->getLevelPriceId())
            : null;
        $property = $this->propertyRepository->findById(Id::fromInt($purchase->getPropertyId()));

        return PlacementPurchaseDTO::fromEntity($purchase, $levelPrice, $property?->getTitle());
    }
}
