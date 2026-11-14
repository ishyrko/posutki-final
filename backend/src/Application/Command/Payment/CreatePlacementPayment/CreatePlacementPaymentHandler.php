<?php

declare(strict_types=1);

namespace App\Application\Command\Payment\CreatePlacementPayment;

use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Enum\PaymentMethod;
use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use App\Domain\Property\Enum\PlacementPurchaseKind;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Payment\BePaid\BePaidClientRegistry;
use Symfony\Component\Uid\Uuid;

final class CreatePlacementPaymentHandler
{
    public function __construct(
        private readonly PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly BePaidClientRegistry $bePaidClients,
        private readonly string $frontendUrl,
        private readonly string $backendUrl,
    ) {
    }

    public function __invoke(CreatePlacementPaymentCommand $command): array
    {
        $userId = Id::fromString($command->userId);
        $purchase = $this->purchaseRepository->findById($command->purchaseId);

        if ($purchase === null) {
            throw new DomainException('Заявка не найдена');
        }
        if (!$purchase->getOwnerId()->equals($userId)) {
            throw new DomainException('Нет прав на эту заявку');
        }
        if (!$purchase->isPendingPayment()) {
            throw new DomainException('Оплатить можно только заявку, ожидающую оплаты');
        }
        if (!$purchase->isReservationValid()) {
            throw new DomainException('Срок резервирования заявки истёк');
        }

        $method = PaymentMethod::tryFrom($command->method);
        if ($method === null) {
            throw new DomainException('Неизвестный способ оплаты');
        }

        $provider = Payment::providerFor($method);
        $bePaidClient = $this->bePaidClients->forMethod($method);

        $existingPending = $this->paymentRepository->findLatestForPurchase($command->purchaseId);
        if (
            $existingPending !== null
            && $existingPending->isPending()
            && $existingPending->getCheckoutToken() !== null
            && $existingPending->getProvider() === $provider
        ) {
            $status = $bePaidClient->getCheckoutStatus($existingPending->getCheckoutToken());
            $redirectUrl = $status['checkout']['redirect_url'] ?? null;
            if (is_string($redirectUrl) && $redirectUrl !== '') {
                return [
                    'redirectUrl' => $redirectUrl,
                    'paymentId' => $existingPending->getId(),
                ];
            }
        }

        $trackingId = sprintf('plc-%d-%s', $command->purchaseId, Uuid::v4()->toRfc4122());
        $payment = new Payment(
            purchaseId: $command->purchaseId,
            trackingId: $trackingId,
            amountByn: $purchase->getPriceByn(),
            provider: $provider,
        );
        $this->paymentRepository->save($payment);

        $property = $this->propertyRepository->findById(Id::fromInt($purchase->getPropertyId()));

        $kindLabel = PlacementPurchaseKind::tryFrom($purchase->getKind())?->label() ?? $purchase->getKind();
        $levelPart = $purchase->getLevel() !== null ? sprintf(' (VIP %d)', $purchase->getLevel()) : '';
        $description = $purchase->isBoost()
            ? sprintf('Размещение объявления #%d: %s', $purchase->getPropertyId(), $kindLabel)
            : sprintf(
                'Размещение объявления #%d: %s%s, %d мес.',
                $purchase->getPropertyId(),
                $kindLabel,
                $levelPart,
                $purchase->getDurationMonths(),
            );

        $frontendBase = rtrim($this->frontendUrl, '/');
        $purchasePath = sprintf('%s/kabinet/oplata/%d/', $frontendBase, $command->purchaseId);
        $backendBase = rtrim($this->backendUrl, '/');
        $notificationPath = $method === PaymentMethod::Epos
            ? '/api/webhooks/bepaid/epos'
            : '/api/webhooks/bepaid';

        $settings = [
            'success_url' => $purchasePath . '?status=success',
            'decline_url' => $purchasePath . '?status=decline',
            'fail_url' => $purchasePath . '?status=fail',
            'cancel_url' => $purchasePath . '?status=cancel',
            'notification_url' => $backendBase . $notificationPath,
            'language' => 'ru',
        ];

        $order = [
            'tracking_id' => $trackingId,
            'currency' => 'BYN',
            'amount' => $purchase->getPriceByn() * 100,
            'description' => $description,
        ];

        if ($method === PaymentMethod::Epos) {
            $reservationExpiresAt = $purchase->getReservationExpiresAt();
            if ($reservationExpiresAt !== null) {
                $order['expired_at'] = $reservationExpiresAt->format(\DateTimeInterface::ATOM);
            }
        }

        $customer = [];
        if ($command->customerEmail !== null && $command->customerEmail !== '') {
            $customer['email'] = $command->customerEmail;
        }
        if ($command->customerIp !== null && $command->customerIp !== '') {
            $customer['ip'] = $command->customerIp;
        }

        $checkout = $bePaidClient->createCheckout(
            $order,
            $settings,
            $customer !== [] ? $customer : null,
        );

        $payment->setCheckoutToken($checkout['token']);
        $this->paymentRepository->save($payment);

        return [
            'redirectUrl' => $checkout['redirectUrl'],
            'paymentId' => $payment->getId(),
        ];
    }
}
