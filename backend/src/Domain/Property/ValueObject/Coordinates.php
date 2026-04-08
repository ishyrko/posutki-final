<?php

declare(strict_types=1);

namespace App\Domain\Property\ValueObject;

final readonly class Coordinates
{
    private function __construct(
        private float $latitude,
        private float $longitude
    ) {
        $this->validate();
    }

    public static function create(float $latitude, float $longitude): self
    {
        return new self($latitude, $longitude);
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * Calculate distance to another point in kilometers using Haversine formula
     */
    public function distanceTo(self $other): float
    {
        $earthRadius = 6371; // km

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($other->latitude);
        $lonTo = deg2rad($other->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function validate(): void
    {
        if ($this->latitude < -90 || $this->latitude > 90) {
            throw new \InvalidArgumentException('Широта должна быть от -90 до 90');
        }

        if ($this->longitude < -180 || $this->longitude > 180) {
            throw new \InvalidArgumentException('Долгота должна быть от -180 до 180');
        }
    }
}