<?php

declare(strict_types=1);

namespace App\Domain\Property\ValueObject;

final readonly class Address
{
    private function __construct(
        private string $building,
        private ?string $block = null
    ) {
    }

    public static function create(string $building, ?string $block = null): self
    {
        return new self($building, $block);
    }

    public function getBuilding(): string
    {
        return $this->building;
    }

    public function getBlock(): ?string
    {
        return $this->block;
    }
}
