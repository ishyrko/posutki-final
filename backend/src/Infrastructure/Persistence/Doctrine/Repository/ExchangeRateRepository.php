<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Exchange\Entity\ExchangeRate;
use App\Domain\Exchange\Repository\ExchangeRateRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExchangeRateRepository extends ServiceEntityRepository implements ExchangeRateRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExchangeRate::class);
    }

    public function save(ExchangeRate $rate): void
    {
        $this->getEntityManager()->persist($rate);
        $this->getEntityManager()->flush();
    }

    public function findByCurrency(string $currency): ?ExchangeRate
    {
        return $this->find(strtoupper($currency));
    }

    public function getAllRates(): array
    {
        $rates = $this->findAll();
        $map = ['BYN' => 1.0];

        foreach ($rates as $rate) {
            $map[$rate->getCurrency()] = $rate->getRateToByn();
        }

        return $map;
    }
}
