<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

final class GeocoderPlaces
{
    /**
     * @param list<string> $microdistrictOfficialNames
     * @param list<string> $residentialComplexOfficialNames
     */
    public function __construct(
        public readonly ?string $adminDistrictOfficialName = null,
        public readonly array $microdistrictOfficialNames = [],
        public readonly array $residentialComplexOfficialNames = [],
    ) {
    }

    public function primaryMicrodistrictOfficialName(): ?string
    {
        return $this->microdistrictOfficialNames[0] ?? null;
    }

    public function primaryResidentialComplexOfficialName(): ?string
    {
        return $this->residentialComplexOfficialNames[0] ?? null;
    }
}
