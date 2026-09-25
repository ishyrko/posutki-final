<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

final class CityBoundaryProvider
{
    /** @var array<string, array{bbox: list<float>, rings: list<list<list<float>>>}>|null */
    private ?array $boundaries = null;

    public function __construct(
        private readonly string $boundariesPath,
    ) {
    }

    /**
     * @return array{bbox: list<float>, rings: list<list<list<float>>>}|null
     */
    public function getBySlug(string $slug): ?array
    {
        $this->load();

        return $this->boundaries[$slug] ?? null;
    }

    /**
     * @return array<string, array{bbox: list<float>, rings: list<list<list<float>>>}>
     */
    public function getAll(): array
    {
        $this->load();

        return $this->boundaries ?? [];
    }

    private function load(): void
    {
        if ($this->boundaries !== null) {
            return;
        }

        if (!is_readable($this->boundariesPath)) {
            throw new \RuntimeException(sprintf('City boundaries file is not readable: %s', $this->boundariesPath));
        }

        $raw = file_get_contents($this->boundariesPath);
        if ($raw === false) {
            throw new \RuntimeException(sprintf('Failed to read city boundaries file: %s', $this->boundariesPath));
        }

        /** @var array{_meta?: array<string, mixed>, cities: array<string, array{bbox: list<float>, rings: list<list<list<float>>>}>} $decoded */
        $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        $this->boundaries = $decoded['cities'];
    }
}
