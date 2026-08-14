<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Property\Entity\CityMicrodistrict;
use App\Domain\Property\Repository\CityMicrodistrictRepositoryInterface;
use App\Domain\Property\Service\CityMicrodistrictResolverInterface;
use App\Domain\Property\Service\GeocoderResponseProviderInterface;
use Psr\Log\LoggerInterface;

final class YandexCityMicrodistrictResolver implements CityMicrodistrictResolverInterface
{
    public function __construct(
        private readonly CityMicrodistrictRepositoryInterface $microdistrictRepository,
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
    ): ?CityMicrodistrict {
        try {
            $data = $this->geocoderResponseProvider->resolve($latitude, $longitude, $propertyId, $forceRefresh);
            if ($data === null) {
                return null;
            }

            $places = $this->geocoderPlaceExtractor->extract($data);
            foreach ($places->microdistrictOfficialNames as $officialName) {
                $existing = $this->microdistrictRepository->findByCityIdAndOfficialName($cityId, $officialName);
                if ($existing !== null) {
                    return $existing;
                }
            }

            return null;
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to resolve city microdistrict from coordinates', [
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
