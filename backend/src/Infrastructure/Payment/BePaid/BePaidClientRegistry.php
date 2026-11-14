<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment\BePaid;

use App\Domain\Payment\Enum\PaymentMethod;
use App\Domain\Shared\Exception\DomainException;

final readonly class BePaidClientRegistry
{
    public function __construct(
        private BePaidGatewayClient $cardClient,
        private BePaidGatewayClient $eposClient,
        private string $eposShopId,
        private string $eposSecretKey,
    ) {
    }

    public function forMethod(PaymentMethod $method): BePaidGatewayClient
    {
        if ($method === PaymentMethod::Epos) {
            if ($this->eposShopId === '' || $this->eposSecretKey === '') {
                throw new DomainException('Оплата через ЕРИП временно недоступна');
            }

            return $this->eposClient;
        }

        return $this->cardClient;
    }
}
