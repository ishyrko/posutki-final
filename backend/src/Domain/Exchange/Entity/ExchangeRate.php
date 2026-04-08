<?php

declare(strict_types=1);

namespace App\Domain\Exchange\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'exchange_rates')]
class ExchangeRate
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4, name: 'rate_to_byn')]
    private string $rateToByn;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $currency, float $rateToByn)
    {
        $this->currency = strtoupper($currency);
        $this->rateToByn = (string) $rateToByn;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getRateToByn(): float
    {
        return (float) $this->rateToByn;
    }

    public function updateRate(float $rateToByn): void
    {
        $this->rateToByn = (string) $rateToByn;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
