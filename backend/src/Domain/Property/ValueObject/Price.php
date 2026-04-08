<?php

declare(strict_types=1);

namespace App\Domain\Property\ValueObject;

final readonly class Price
{
    private function __construct(
        private int $amount,      // в копейках для точности
        private string $currency = 'BYN'
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Цена не может быть отрицательной');
        }
    }

    public static function fromAmount(int $amount, string $currency = 'BYN'): self
    {
        return new self($amount, $currency);
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getFormatted(): string
    {
        $value = $this->amount / 100;
        return number_format($value, 2, '.', ' ') . ' ' . $this->currency;
    }

    public function calculatePricePerMeter(float $area): self
    {
        if ($area <= 0) {
            throw new \InvalidArgumentException('Площадь должна быть больше нуля');
        }

        $pricePerMeter = (int) round($this->amount / $area);
        return new self($pricePerMeter, $this->currency);
    }

    public function __toString(): string
    {
        return $this->getFormatted();
    }
}