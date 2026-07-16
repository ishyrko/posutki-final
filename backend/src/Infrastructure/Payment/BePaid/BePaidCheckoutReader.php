<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment\BePaid;

final class BePaidCheckoutReader
{
    /**
     * @param array<string, mixed> $body
     */
    public function readStatus(array $body): ?string
    {
        $checkout = $body['checkout'] ?? null;
        if (!is_array($checkout)) {
            return null;
        }

        $payment = $checkout['gateway_response']['payment'] ?? null;
        if (is_array($payment) && isset($payment['status']) && is_string($payment['status']) && $payment['status'] !== '') {
            return strtolower($payment['status']);
        }

        $status = $checkout['status'] ?? null;
        if (is_string($status) && $status !== '') {
            return strtolower($status);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $body
     */
    public function readTransactionUid(array $body): ?string
    {
        $checkout = $body['checkout'] ?? null;
        if (!is_array($checkout)) {
            return null;
        }

        $payment = $checkout['gateway_response']['payment'] ?? null;
        if (is_array($payment) && isset($payment['uid']) && is_string($payment['uid']) && $payment['uid'] !== '') {
            return $payment['uid'];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>|null
     */
    public function readOrder(array $body): ?array
    {
        $checkout = $body['checkout'] ?? null;
        if (!is_array($checkout)) {
            return null;
        }

        $order = $checkout['order'] ?? null;

        return is_array($order) ? $order : null;
    }
}
