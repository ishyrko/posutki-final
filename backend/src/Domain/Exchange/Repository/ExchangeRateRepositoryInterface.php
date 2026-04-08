<?php

declare(strict_types=1);

namespace App\Domain\Exchange\Repository;

use App\Domain\Exchange\Entity\ExchangeRate;

interface ExchangeRateRepositoryInterface
{
    public function save(ExchangeRate $rate): void;

    public function findByCurrency(string $currency): ?ExchangeRate;

    /** @return array<string, float> currency => rateToByn */
    public function getAllRates(): array;
}
