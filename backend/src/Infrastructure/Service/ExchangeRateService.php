<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Exchange\Entity\ExchangeRate;
use App\Domain\Exchange\Repository\ExchangeRateRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ExchangeRateService
{
    private const NBRB_RATES_URL = 'https://api.nbrb.by/exrates/rates';

    private const NBRB_CURRENCY_IDS = [
        'USD' => 431,
        'EUR' => 451,
    ];

    public function __construct(
        private readonly ExchangeRateRepositoryInterface $rateRepository,
        private readonly EntityManagerInterface $em,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function fetchAndUpdateRates(): array
    {
        $updated = [];

        foreach (self::NBRB_CURRENCY_IDS as $currency => $curId) {
            try {
                $response = $this->httpClient->request('GET', self::NBRB_RATES_URL . '/' . $curId);
                $data = $response->toArray();

                $rate = $data['Cur_OfficialRate'] / $data['Cur_Scale'];

                $entity = $this->rateRepository->findByCurrency($currency);
                if ($entity === null) {
                    $entity = new ExchangeRate($currency, $rate);
                } else {
                    $entity->updateRate($rate);
                }

                $this->rateRepository->save($entity);
                $updated[$currency] = $rate;

                $this->logger->info("Exchange rate updated: {$currency} = {$rate} BYN");
            } catch (\Throwable $e) {
                $this->logger->error("Failed to fetch {$currency} rate: " . $e->getMessage());
            }
        }

        return $updated;
    }

    public function recalculateAllPropertyPrices(): int
    {
        $rates = $this->rateRepository->getAllRates();
        $count = 0;

        $batchSize = 100;
        $offset = 0;

        do {
            $properties = $this->em
                ->createQuery('SELECT p FROM App\Domain\Property\Entity\Property p')
                ->setFirstResult($offset)
                ->setMaxResults($batchSize)
                ->getResult();

            foreach ($properties as $property) {
                $currency = $property->getPrice()->getCurrency();
                $amount = $property->getPrice()->getAmount();
                $rate = $rates[$currency] ?? 1.0;

                $property->setPriceByn((int) round($amount * $rate));
                $count++;
            }

            $this->em->flush();
            $this->em->clear();

            $offset += $batchSize;
        } while (count($properties) === $batchSize);

        return $count;
    }

    public function calculatePriceByn(int $amount, string $currency): int
    {
        if ($currency === 'BYN') {
            return $amount;
        }

        $rates = $this->rateRepository->getAllRates();
        $rate = $rates[$currency] ?? 1.0;

        return (int) round($amount * $rate);
    }

    public function convertToByn(float $amount, string $fromCurrency): float
    {
        if ($fromCurrency === 'BYN') {
            return $amount;
        }

        $rates = $this->rateRepository->getAllRates();
        $rate = $rates[$fromCurrency] ?? 1.0;

        return $amount * $rate;
    }

    public function getRates(): array
    {
        return $this->rateRepository->getAllRates();
    }
}
