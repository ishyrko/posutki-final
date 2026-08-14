<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Property\Entity\CityDistrict;
use App\Domain\Property\Repository\CityDistrictRepositoryInterface;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Service\CitiesWithDistricts;
use App\Domain\Property\Service\CityDistrictResolverInterface;
use App\Domain\Property\Service\GeocoderResponseProviderInterface;
use Psr\Log\LoggerInterface;

final class YandexCityDistrictResolver implements CityDistrictResolverInterface
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
        private readonly CityDistrictRepositoryInterface $cityDistrictRepository,
        private readonly GeocoderResponseProviderInterface $geocoderResponseProvider,
        private readonly GeocoderPlaceExtractor $geocoderPlaceExtractor,
        private readonly CityDistrictSlugGenerator $cityDistrictSlugGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function resolve(
        float $latitude,
        float $longitude,
        int $cityId,
        ?int $propertyId = null,
        bool $forceRefresh = false,
    ): ?CityDistrict {
        $city = $this->cityRepository->findById($cityId);
        if ($city === null || !CitiesWithDistricts::supportsSlug($city->getSlug())) {
            return null;
        }

        try {
            $data = $this->geocoderResponseProvider->resolve($latitude, $longitude, $propertyId, $forceRefresh);
            if ($data === null) {
                return null;
            }

            $places = $this->geocoderPlaceExtractor->extract($data);
            $districtName = $places->adminDistrictOfficialName;
            if ($districtName === null || $districtName === '') {
                return null;
            }

            $existing = $this->cityDistrictRepository->findByCityIdAndOfficialName($cityId, $districtName);
            if ($existing !== null) {
                return $existing;
            }

            $slug = $this->cityDistrictSlugGenerator->generateSlug($cityId, $districtName);
            $displayName = CityDistrictSlugGenerator::stripDistrictSuffix($districtName);
            if ($displayName === '') {
                $displayName = $districtName;
            }

            $cityDistrict = new CityDistrict($cityId, $districtName, $displayName, $slug);
            $this->cityDistrictRepository->save($cityDistrict);

            return $this->cityDistrictRepository->findByCityIdAndOfficialName($cityId, $districtName) ?? $cityDistrict;
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to resolve city district from coordinates', [
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
