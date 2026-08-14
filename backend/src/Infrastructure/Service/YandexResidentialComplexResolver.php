<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Property\Entity\ResidentialComplex;
use App\Domain\Property\Repository\ResidentialComplexRepositoryInterface;
use App\Domain\Property\Service\GeocoderResponseProviderInterface;
use App\Domain\Property\Service\ResidentialComplexResolverInterface;
use Psr\Log\LoggerInterface;

final class YandexResidentialComplexResolver implements ResidentialComplexResolverInterface
{
    public function __construct(
        private readonly ResidentialComplexRepositoryInterface $residentialComplexRepository,
        private readonly GeocoderResponseProviderInterface $geocoderResponseProvider,
        private readonly GeocoderPlaceExtractor $geocoderPlaceExtractor,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function resolve(
        float $latitude,
        float $longitude,
        int $cityId,
        ?int $propertyId = null,
        bool $forceRefresh = false,
    ): ?ResidentialComplex {
        try {
            $data = $this->geocoderResponseProvider->resolve($latitude, $longitude, $propertyId, $forceRefresh);
            if ($data === null) {
                return null;
            }

            $places = $this->geocoderPlaceExtractor->extract($data);
            foreach ($places->residentialComplexOfficialNames as $officialName) {
                $existing = $this->residentialComplexRepository->findByCityIdAndOfficialName($cityId, $officialName);
                if ($existing !== null) {
                    return $existing;
                }
            }

            return null;
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to resolve residential complex from coordinates', [
                'cityId' => $cityId,
                'propertyId' => $propertyId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
