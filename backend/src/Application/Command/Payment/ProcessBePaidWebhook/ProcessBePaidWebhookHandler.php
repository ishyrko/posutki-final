<?php

declare(strict_types=1);

namespace App\Application\Command\Payment\ProcessBePaidWebhook;

use App\Application\Service\PlacementPaymentCompletionService;
use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use Psr\Log\LoggerInterface;

final class ProcessBePaidWebhookHandler
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly PlacementPaymentCompletionService $completionService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProcessBePaidWebhookCommand $command): void
    {
        if (!$command->signatureValid) {
            $this->logger->warning('bePaid webhook rejected: invalid signature');

            return;
        }

        $payload = $command->payload;
        $payment = $this->resolvePayment($payload);
        if ($payment === null) {
            $this->logger->warning('bePaid webhook: payment not found', [
                'token' => $this->extractToken($payload),
                'tracking_id' => $this->extractTrackingId($payload),
            ]);

            return;
        }

        $payment->setRawPayload($command->rawBody);

        if ($payment->isTerminal()) {
            $this->paymentRepository->save($payment);

            return;
        }

        if (!$this->verifyAmountAndTracking($payment, $payload)) {
            $payment->markFailed('Несовпадение суммы или tracking_id в уведомлении bePaid');
            $this->paymentRepository->save($payment);

            return;
        }

        $bePaidStatus = $this->extractStatus($payload);
        $transactionUid = $this->extractTransactionUid($payload);
        $isExpired = (bool) ($payload['expired'] ?? false);

        if ($bePaidStatus === 'successful') {
            $this->handleSuccessful($payment, $transactionUid);
        } elseif ($isExpired || $bePaidStatus === 'error') {
            $payment->markExpired($payload['message'] ?? null);
            $this->paymentRepository->save($payment);
        } elseif (in_array($bePaidStatus, ['failed', 'declined', 'fail'], true)) {
            $payment->markFailed($payload['message'] ?? null);
            $this->paymentRepository->save($payment);
        } elseif ($bePaidStatus === 'cancelled' || $bePaidStatus === 'canceled') {
            $payment->markCancelled($payload['message'] ?? null);
            $this->paymentRepository->save($payment);
        } else {
            $this->paymentRepository->save($payment);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolvePayment(array $payload): ?Payment
    {
        $token = $this->extractToken($payload);
        if ($token !== null) {
            $byToken = $this->paymentRepository->findByCheckoutToken($token);
            if ($byToken !== null) {
                return $byToken;
            }
        }

        $trackingId = $this->extractTrackingId($payload);
        if ($trackingId !== null) {
            return $this->paymentRepository->findByTrackingId($trackingId);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractToken(array $payload): ?string
    {
        $token = $payload['token'] ?? null;
        if (is_string($token) && $token !== '') {
            return $token;
        }

        $checkoutToken = $payload['checkout']['token'] ?? null;
        if (is_string($checkoutToken) && $checkoutToken !== '') {
            return $checkoutToken;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractTrackingId(array $payload): ?string
    {
        $order = $payload['order'] ?? null;
        if (is_array($order) && isset($order['tracking_id']) && is_string($order['tracking_id']) && $order['tracking_id'] !== '') {
            return $order['tracking_id'];
        }

        $transaction = $payload['transaction'] ?? null;
        if (is_array($transaction) && isset($transaction['tracking_id']) && is_string($transaction['tracking_id']) && $transaction['tracking_id'] !== '') {
            return $transaction['tracking_id'];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractStatus(array $payload): ?string
    {
        $status = $payload['status'] ?? null;
        if (is_string($status) && $status !== '') {
            return strtolower($status);
        }

        $transaction = $payload['transaction'] ?? null;
        if (is_array($transaction) && isset($transaction['status']) && is_string($transaction['status'])) {
            return strtolower($transaction['status']);
        }

        $payment = $payload['transaction']['payment']['status'] ?? null;
        if (is_string($payment) && $payment !== '') {
            return strtolower($payment);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractTransactionUid(array $payload): ?string
    {
        $uid = $payload['uid'] ?? null;
        if (is_string($uid) && $uid !== '') {
            return $uid;
        }

        $transaction = $payload['transaction'] ?? null;
        if (is_array($transaction) && isset($transaction['uid']) && is_string($transaction['uid'])) {
            return $transaction['uid'];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function verifyAmountAndTracking(Payment $payment, array $payload): bool
    {
        $trackingId = $this->extractTrackingId($payload);
        if ($trackingId !== null && $trackingId !== $payment->getTrackingId()) {
            return false;
        }

        $amountMinor = null;
        $currency = null;

        $order = $payload['order'] ?? null;
        if (is_array($order)) {
            $amountMinor = $order['amount'] ?? null;
            $currency = $order['currency'] ?? null;
        }

        $transaction = $payload['transaction'] ?? null;
        if (is_array($transaction)) {
            $amountMinor ??= $transaction['amount'] ?? null;
            $currency ??= $transaction['currency'] ?? null;
        }

        if ($amountMinor !== null && (int) $amountMinor !== $payment->getAmountByn() * 100) {
            return false;
        }

        if ($currency !== null && strtoupper((string) $currency) !== 'BYN') {
            return false;
        }

        return true;
    }

    private function handleSuccessful(Payment $payment, ?string $transactionUid): void
    {
        try {
            $this->completionService->completeSuccessfulPayment($payment, $transactionUid);
        } catch (\Throwable) {
            // Note and logging are handled in the completion service.
        }
    }
}
